<?php
session_start();
if (!isset($_SESSION["username"])) { header("Location: login.php"); exit; }

$host = "localhost"; $user = "root"; $password = ""; $db = "logindetails_db";
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Connection failed: ".$conn->connect_error); }

$pid = isset($_GET['pid']) ? (int)$_GET['pid'] : 0;

// Fetch project
$stmt = $conn->prepare("SELECT * FROM project WHERE project_number = ?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$project = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$project) { echo "Project not found."; exit; }

$altId = (int)$project['project_id'];

/* ===== Remove actions ===== */
if (isset($_GET['remove_subleader'])) {
    $uid = (int)$_GET['remove_subleader'];
    $stmt = $conn->prepare("DELETE FROM sub_leaders WHERE employee_id = ? AND project_id = ?");
    $stmt->bind_param("ii", $uid, $pid);
    $stmt->execute();
    $stmt->close();
    header("Location: project_view.php?pid=" . $pid);
    exit;
}

if (isset($_GET['remove_employee'])) {
    $uid = (int)$_GET['remove_employee'];
    $stmt = $conn->prepare("DELETE FROM assignments WHERE employee_id = ? AND (project_id = ? OR project_id = ?)");
    $stmt->bind_param("iii", $uid, $pid, $altId);
    $stmt->execute();
    $stmt->close();
    header("Location: project_view.php?pid=" . $pid);
    exit;
}

if (isset($_GET['remove_leader'])) {
    $stmt = $conn->prepare("UPDATE project SET project_leader = '' WHERE project_number = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $stmt->close();
    header("Location: project_view.php?pid=" . $pid);
    exit;
}

/* ===== Add leader ===== */
if (isset($_POST['add_leader']) && !empty($_POST['leader_id'])) {
    $leaderId = (int)$_POST['leader_id'];
    // get username from lgntable
    $stmt = $conn->prepare("SELECT username FROM lgntable WHERE id = ? AND usertype = 'projectleader'");
    $stmt->bind_param("i", $leaderId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $leaderName = $row['username'];
        $stmt2 = $conn->prepare("UPDATE project SET project_leader = ? WHERE project_number = ?");
        $stmt2->bind_param("si", $leaderName, $pid);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();
    header("Location: project_view.php?pid=" . $pid);
    exit;
}

/* ===== Add employee ===== */
if (isset($_POST['add_employee']) && !empty($_POST['employee_id'])) {
    $empId = (int)$_POST['employee_id'];
    $stmt = $conn->prepare("INSERT INTO assignments (project_id, employee_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $pid, $empId);
    $stmt->execute();
    $stmt->close();
    header("Location: project_view.php?pid=" . $pid);
    exit;
}

/* ===== Sub leaders ===== */
$subLeaders = [];
$stmt = $conn->prepare("
    SELECT l.id, l.username
    FROM sub_leaders s
    JOIN lgntable l ON l.id = s.employee_id
    WHERE s.project_id = ?
    ORDER BY l.username
");
$stmt->bind_param("i", $pid);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $subLeaders[] = $r;
$stmt->close();

/* ===== Employees ===== */
$employees = [];
$stmt = $conn->prepare("
    SELECT DISTINCT l.id, l.username
    FROM assignments a
    JOIN lgntable l ON l.id = a.employee_id
    WHERE (a.project_id = ? OR a.project_id = ?)
    ORDER BY l.username
");
$stmt->bind_param("ii", $pid, $altId);
$stmt->execute();
$res = $stmt->get_result();
while ($r = $res->fetch_assoc()) $employees[] = $r;
$stmt->close();

/* ===== Potential leaders to add ===== */
$availableLeaders = [];
$stmt = $conn->prepare("
    SELECT id, username FROM lgntable
    WHERE usertype = 'projectleader'
    ORDER BY username
");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    if ($row['username'] !== $project['project_leader']) {
        $availableLeaders[] = $row;
    }
}
$stmt->close();

/* ===== Potential employees to add ===== */
$availableEmployees = [];
$stmt = $conn->prepare("
    SELECT id, username FROM lgntable
    WHERE usertype = 'employee' AND id NOT IN (
        SELECT employee_id FROM assignments WHERE project_id = ? OR project_id = ?
    )
    ORDER BY username
");
$stmt->bind_param("ii", $pid, $altId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $availableEmployees[] = $row;
}
$stmt->close();

/* ===== Charts data ===== */
$goal_total = 0;
$goal_completed = 0;
$stmt = $conn->prepare("SELECT COUNT(*) FROM project_goals WHERE project_id = ?");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($goal_total);
$stmt->fetch();
$stmt->close();

$stmt = $conn->prepare("SELECT COUNT(*) FROM project_goals WHERE project_id = ? AND is_completed = 1");
$stmt->bind_param("i", $pid);
$stmt->execute();
$stmt->bind_result($goal_completed);
$stmt->fetch();
$stmt->close();

$total_emps = 0;
$emps_on_project = 0;
$q = $conn->query("SELECT COUNT(*) AS c FROM lgntable WHERE usertype = 'employee'");
if ($q) { $row = $q->fetch_assoc(); $total_emps = (int)$row['c']; }

$stmt = $conn->prepare("
    SELECT COUNT(DISTINCT a.employee_id) 
    FROM assignments a 
    JOIN lgntable l ON l.id = a.employee_id
    WHERE (a.project_id = ? OR a.project_id = ?) AND l.usertype = 'employee'
");
$stmt->bind_param("ii", $pid, $altId);
$stmt->execute();
$stmt->bind_result($emps_on_project);
$stmt->fetch();
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
  <title>Project #<?= htmlspecialchars($project['project_number']) ?></title>
  <style>
    body { font-family: Arial, sans-serif; background: #f4f6f9; margin: 0; }
    .top-bar { background: #003366; padding: 12px 20px; color: white; display: flex; justify-content: space-between; align-items: center; }
    .top-bar a { color: white; text-decoration: none; background: #cc0000; padding: 6px 12px; border-radius: 6px; margin-left: 8px; }
    .main-content { padding: 20px; max-width: 1100px; margin: auto; }
    .project-box { background: white; border-radius: 10px; padding: 20px; margin-bottom: 20px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
    .charts-row { display: flex; gap: 32px; flex-wrap: wrap; justify-content: center; margin-top: 16px; }
    .chart-card { background: #fff; border-radius: 12px; padding: 16px 18px; box-shadow: 0 6px 18px rgba(13,38,76,.08); width: 320px; max-width: 90%; }
    .chart-card h4 { margin: 6px 0 10px 0; text-align: center; color: #003366; }
    .chart-card canvas { width: 100% !important; height: 220px !important; display: block; }
    .chart-card p { text-align: center; margin: 8px 0 0 0; }
    .user-list { list-style: none; padding: 0; }
    .user-list li { background: #f9f9f9; margin-bottom: 6px; padding: 8px; border-radius: 6px; }
    .remove-link { color: red; margin-left: 10px; text-decoration: none; font-size: 0.9em; }
    .remove-link:hover { text-decoration: underline; }
  </style>
</head>
<body>
<div class="top-bar">
  <div>Project Details</div>
  <div>
    <a href="subdirector.php" style="color: white; background-color: green;">Back</a>
    <a href="logout.php">Logout</a>
  </div>
</div>

<div class="main-content">
  <div class="project-box">
    <h3>#<?= $project['project_number'] ?> — <?= htmlspecialchars($project['project_name'] ?: 'Untitled') ?></h3>
    <p>
      <strong>Leader:</strong> <?= htmlspecialchars($project['project_leader'] ?: '—') ?>
      <?php if (!empty($project['project_leader'])): ?>
        <a class="remove-link" href="?pid=<?= $pid ?>&remove_leader=1" onclick="return confirm('Remove this project leader?')">[Remove]</a>
      <?php endif; ?>
    </p>
    <?php if (empty($project['project_leader']) && !empty($availableLeaders)): ?>
      <form method="post" style="margin-top:10px;">
        <select name="leader_id" required>
          <option value="">-- Select Leader --</option>
          <?php foreach ($availableLeaders as $al): ?>
            <option value="<?= $al['id'] ?>"><?= htmlspecialchars($al['username']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="add_leader">Add Leader</button>
      </form>
    <?php endif; ?>
    <p><strong>Cost:</strong> <?= $project['project_cost'] !== null ? '₹'.number_format((float)$project['project_cost'],2) : '—' ?></p>
    <p><strong>Proposal:</strong> <?= htmlspecialchars($project['project_proposal']) ?></p>
    <p><strong>Sanction:</strong> <?= htmlspecialchars($project['project_sanction']) ?></p>
    <p><strong>Completed:</strong> <?= htmlspecialchars($project['project_completed']) ?></p>
    <p><strong>Objective:</strong> <?= htmlspecialchars($project['project_objective']) ?></p>
  </div>

  <div class="charts-row">
    <div class="chart-card">
      <h4>Goals Progress</h4>
      <canvas id="goalsChart"></canvas>
      <p><small>Total goals: <strong><?= (int)$goal_total ?></strong> • Completed: <strong><?= (int)$goal_completed ?></strong></small></p>
    </div>

    <div class="chart-card">
      <h4>Employees on Project</h4>
      <canvas id="employeesChart"></canvas>
      <p><small>On project: <strong><?= (int)$emps_on_project ?></strong> • Total employees: <strong><?= (int)$total_emps ?></strong></small></p>
    </div>
  </div>

  <div class="project-box">
    <h3>Sub Leaders</h3>
    <?php if ($subLeaders): ?>
      <ul class="user-list">
        <?php foreach ($subLeaders as $sl): ?>
          <li>
            <?= htmlspecialchars($sl['username']) ?> (sub-leader)
            <a class="remove-link" href="?pid=<?= $pid ?>&remove_subleader=<?= $sl['id'] ?>" onclick="return confirm('Remove this sub-leader?')">[Remove]</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No sub leaders assigned.</p>
    <?php endif; ?>
  </div>

  <div class="project-box">
    <h3>Assigned Employees</h3>
    <?php if ($employees): ?>
      <ul class="user-list">
        <?php foreach ($employees as $e): ?>
          <li>
            <?= htmlspecialchars($e['username']) ?> (employee)
            <a class="remove-link" href="?pid=<?= $pid ?>&remove_employee=<?= $e['id'] ?>" onclick="return confirm('Remove this employee?')">[Remove]</a>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php else: ?>
      <p>No employees assigned.</p>
    <?php endif; ?>

    <?php if (!empty($availableEmployees)): ?>
      <form method="post" style="margin-top:10px;">
        <select name="employee_id" required>
          <option value="">-- Select Employee --</option>
          <?php foreach ($availableEmployees as $ae): ?>
            <option value="<?= $ae['id'] ?>"><?= htmlspecialchars($ae['username']) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" name="add_employee">Add Employee</button>
      </form>
    <?php endif; ?>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
(function () {
  var goalCompleted = <?= json_encode((int)$goal_completed) ?>;
  var goalPending   = Math.max(<?= json_encode((int)$goal_total) ?> - goalCompleted, 0);
  var empsOn        = <?= json_encode((int)$emps_on_project) ?>;
  var totalEmps     = <?= json_encode((int)$total_emps) ?>;
  var empsNot       = Math.max(totalEmps - empsOn, 0);

  var g = document.getElementById('goalsChart');
  if (g) {
    new Chart(g.getContext('2d'), {
      type: 'doughnut',
      data: { labels: ['Completed', 'Pending'], datasets: [{ data: [goalCompleted, goalPending], backgroundColor: ['#4CAF50', '#E0E0E0'], hoverOffset: 6 }] },
      options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });
  }

  var e = document.getElementById('employeesChart');
  if (e) {
    new Chart(e.getContext('2d'), {
      type: 'doughnut',
      data: { labels: ['On Project', 'Not on Project'], datasets: [{ data: [empsOn, empsNot], backgroundColor: ['#2196F3', '#E0E0E0'], hoverOffset: 6 }] },
      options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
    });
  }
})();
</script>
</body>
</html>
