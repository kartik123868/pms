<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"];
if (!isset($_GET['project_id'])) {
    die("No project selected.");
}

$project_id = intval($_GET['project_id']);

/* ---------------------------
   Fetch project (scoped to leader)
---------------------------- */
$stmt = $conn->prepare("SELECT * FROM project WHERE project_number = ? AND project_leader = ? LIMIT 1");
$stmt->bind_param("is", $project_id, $username);
$stmt->execute();
$result = $stmt->get_result();
if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
} else {
    $stmt->close();
    die("Project not found or access denied.");
}
$stmt->close();

/* ---------------------------
   Assign Employee (unchanged)
---------------------------- */
if (isset($_POST['assign'])) {
    $employee_id = intval($_POST['employee_id']);
    $ins = $conn->prepare("INSERT INTO assignments (employee_id, project_id) VALUES (?, ?)");
    $ins->bind_param("ii", $employee_id, $project_id);
    $ins->execute();
    $ins->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

if (isset($_GET['remove_assignment'])) {
    $assign_id = intval($_GET['remove_assignment']);
    $del = $conn->prepare("DELETE FROM assignments WHERE id = ?");
    $del->bind_param("i", $assign_id);
    $del->execute();
    $del->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

/* -------------------------------------------------------
   TASKS: Add / Edit / Delete (multiple tasks per employee)
   Note: Status removed from page UI. New tasks inserted with 'Pending'.
-------------------------------------------------------- */
if (isset($_POST['add_task'])) {
    $employee_id      = intval($_POST['employee_id']);
    $task_title       = trim($_POST['task_title'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $priority         = $_POST['priority'] ?? 'Medium';
    $due_date         = $_POST['due_date'] ?? null;
    $status           = 'Pending'; // default, page does not expose status control

    // Ensure employee belongs to this project
    $chk = $conn->prepare("SELECT 1 FROM assignments WHERE project_id = ? AND employee_id = ? LIMIT 1");
    $chk->bind_param("ii", $project_id, $employee_id);
    $chk->execute();
    $chk_res = $chk->get_result();
    $chk->close();
    if ($chk_res && $chk_res->num_rows === 0) {
        header("Location: project_details.php?project_id=$project_id");
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO tasks (employee_id, project_id, task_title, task_description, priority, due_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssss", $employee_id, $project_id, $task_title, $task_description, $priority, $due_date, $status);
    $stmt->execute();
    $stmt->close();

    header("Location: project_details.php?project_id=$project_id#tab2");
    exit;
}

if (isset($_POST['edit_task'])) {
    $task_id          = intval($_POST['task_id']);
    $task_title       = trim($_POST['task_title'] ?? '');
    $task_description = trim($_POST['task_description'] ?? '');
    $priority         = $_POST['priority'] ?? 'Medium';
    $due_date         = $_POST['due_date'] ?? null;

    // Do NOT change status here — keep status managed elsewhere
    $stmt = $conn->prepare("UPDATE tasks SET task_title = ?, task_description = ?, priority = ?, due_date = ? WHERE task_id = ? AND project_id = ?");
    $stmt->bind_param("ssssii", $task_title, $task_description, $priority, $due_date, $task_id, $project_id);
    $stmt->execute();
    $stmt->close();

    header("Location: project_details.php?project_id=$project_id#tab2");
    exit;
}

if (isset($_GET['delete_task'])) {
    $task_id = intval($_GET['delete_task']);
    $del = $conn->prepare("DELETE FROM tasks WHERE task_id = ? AND project_id = ?");
    $del->bind_param("ii", $task_id, $project_id);
    $del->execute();
    $del->close();

    header("Location: project_details.php?project_id=$project_id#tab2");
    exit;
}

/* ---------------------------
   Sub-Leaders (unchanged)
---------------------------- */
if (isset($_POST['assign_subleader'])) {
    $employee_id = intval($_POST['employee_id']);
    $ins = $conn->prepare("INSERT INTO sub_leaders (project_id, employee_id) VALUES (?, ?)");
    $ins->bind_param("ii", $project_id, $employee_id);
    $ins->execute();
    $ins->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

if (isset($_GET['remove_subleader'])) {
    $sub_id = intval($_GET['remove_subleader']);
    $del = $conn->prepare("DELETE FROM sub_leaders WHERE id = ?");
    $del->bind_param("i", $sub_id);
    $del->execute();
    $del->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

/* ---------------------------
   Goals (unchanged)
---------------------------- */
if (isset($_POST['add_goal'])) {
    $goal_text = $_POST['goal_text'] ?? '';
    $ins = $conn->prepare("INSERT INTO project_goals (project_id, goal_text, is_completed) VALUES (?, ?, 0)");
    $ins->bind_param("is", $project_id, $goal_text);
    $ins->execute();
    $ins->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

if (isset($_POST['update_goals'])) {
    if (!empty($_POST['goal_id']) && is_array($_POST['goal_id'])) {
        $reset = $conn->prepare("UPDATE project_goals SET is_completed = 0 WHERE id = ?");
        foreach ($_POST['goal_id'] as $gid) {
            $id = intval($gid);
            $reset->bind_param("i", $id);
            $reset->execute();
        }
        $reset->close();
    }
    if (!empty($_POST['completed_goals']) && is_array($_POST['completed_goals'])) {
        $set = $conn->prepare("UPDATE project_goals SET is_completed = 1 WHERE id = ?");
        foreach ($_POST['completed_goals'] as $cid) {
            $id = intval($cid);
            $set->bind_param("i", $id);
            $set->execute();
        }
        $set->close();
    }
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

if (isset($_GET['remove_goal'])) {
    $goal_id = intval($_GET['remove_goal']);
    $del = $conn->prepare("DELETE FROM project_goals WHERE id = ?");
    $del->bind_param("i", $goal_id);
    $del->execute();
    $del->close();
    header("Location: project_details.php?project_id=$project_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Project Details</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #222; display:flex; min-height:100vh; background:#eef3f9; }
        .sidebar { width:220px; background:#003366; color:#fff; display:flex; flex-direction:column; justify-content:space-between; height:100vh; position:fixed; left:0; top:0; padding-top:18px; }
        .sidebar .brand { text-align:center; font-weight:700; letter-spacing:1px; margin-bottom:10px; padding:8px 12px; font-size:14px; }
        .sidebar .nav{ padding:0 12px; }
        .sidebar .nav a{ display:flex; align-items:center; gap:12px; color:#fff; text-decoration:none; padding:10px 12px; border-radius:6px; margin-bottom:6px; font-size:14px; transition: background .18s, transform .12s; }
        .sidebar .nav a:hover{ background:#0055aa; transform:translateX(4px); }
        .main { margin-left:220px; flex:1; display:flex; flex-direction:column; min-height:100vh; }
        .page{ padding:20px; flex:1; }
        .tab-buttons{ display:flex; gap:8px; margin-bottom:6px; align-items:center; width:100%; }
        .tab-buttons button{ background:#fff; border:0; padding:12px 14px; border-radius:8px; font-weight:700; cursor:pointer; box-shadow:0 1px 3px rgba(0,0,0,.06); transition: transform .12s, background .12s, color .12s; flex:1; text-align:center; }
        .tab-buttons button:hover{ transform:translateY(-3px); background:#eaf4ff; color:#003366; }
        .tab-buttons button.active{ background:linear-gradient(90deg,#0055aa,#003366); color:#fff; }
        .card{ background:#fff; border-radius:10px; padding:18px; box-shadow:0 6px 18px rgba(13,38,76,.06); margin-bottom:18px; }
        select, input[type="text"], input[type="date"], textarea{ padding:8px 10px; border-radius:6px; border:1px solid #ddd; font-size:14px; }
        textarea{ width:100%; min-height:70px; resize:vertical; }
        .small-btn{ padding:8px 12px; border-radius:6px; border:0; cursor:pointer; background:#003366; color:#fff; font-weight:700; margin-left:8px; }
        .small-btn:hover{ background:#0055aa; }
        ul{ list-style:none; padding-left:0; margin:8px 0; }
        li{ margin-bottom:8px; display:flex; align-items:center; gap:8px; }
        .avatar-sm{ width:36px; height:36px; border-radius:50%; object-fit:cover; border:1px solid #ddd; }
        .chart-container{ width:300px; max-width:90%; height:300px; display:flex; flex-direction:column; align-items:stretch; justify-content:flex-start; }
        .chart-container canvas{ width:100% !important; height:220px !important; display:block; }

        /* Table styling for tasks */
        table.tasks { width:100%; border-collapse:collapse; }
        table.tasks th, table.tasks td { border:1px solid #e6e6e6; padding:8px 10px; vertical-align:top; }
        table.tasks th { background:#f7f9fc; text-align:left; }
        table.tasks tr:hover { background:#fafcff; }

        @media (max-width:880px){
            .sidebar{ width:64px; }
            .main{ margin-left:64px; }
            .sidebar .nav a span, .sidebar .brand{ display:none; }
            .sidebar .nav a{ justify-content:center; }
            .sidebar .bottom a{ justify-content:center; padding:10px; }
        }
    </style>
    <script>
        function showTab(tabId, btn) {
            var tabs = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
                tabs[i].style.display = 'none';
            }
            var el = document.getElementById(tabId);
            if (el) {
                el.classList.add('active');
                el.style.display = 'block';
            }
            var bs = document.querySelectorAll('.tab-buttons button');
            bs.forEach(function(b){ b.classList.remove('active'); });
            if (btn) btn.classList.add('active');

            if (typeof window.updateCharts === 'function') {
                setTimeout(window.updateCharts, 80);
            }
        }
        window.addEventListener('DOMContentLoaded', function(){
            var first = document.querySelector('.tab-buttons button');
            if(first) { showTab('tab0', first); }
        });
    </script>
</head>
<body>

    <div class="sidebar">
        <div>
            <div class="brand">Project: <?= htmlspecialchars($project['project_name']) ?></div>
            <nav class="nav">
                <a href="projectleader.php" style="display:flex;align-items:center;text-decoration:none;color:white;font-family:sans-serif;font-size:16px;">
                    <i style="display:inline-block;width:16px;height:16px;background-image:url('https://cdn-icons-png.flaticon.com/512/93/93634.png');background-size:cover;margin-right:8px;filter:brightness(0) invert(1);"></i>
                    <span>Back</span>
                </a>

                <a href="home.php" style="display:flex;align-items:center;text-decoration:none;color:white;font-family:sans-serif;font-size:16px;">
                    <i style="display:inline-block;width:16px;height:16px;background-image:url('https://cdn-icons-png.flaticon.com/512/1828/1828479.png');background-size:cover;margin-right:8px;filter:brightness(0) invert(1);"></i>
                    <span>Logout</span>
                </a>

                <a href="primg.php" style="display:flex;align-items:center;text-decoration:none;color:white;font-family:sans-serif;font-size:16px;">
                    <i style="display:inline-block;width:16px;height:16px;background-image:url('https://cdn-icons-png.flaticon.com/512/1250/1250681.png');background-size:cover;margin-right:8px;filter:brightness(0) invert(1);"></i>
                    <span>Add Pic</span>
                </a>
            </nav>
        </div>
    </div>

    <div class="main">

<?php
// Leader image (unchanged)
$default_img = 'uploads/default.png';
$leader_profile_pic = null;

$g = $conn->prepare("SELECT profile_pic FROM lgntable WHERE username = ? LIMIT 1");
$g->bind_param("s", $project['project_leader']);
$g->execute();
$g->bind_result($leader_profile_pic);
$g->fetch();
$g->close();

$leader_img = $default_img;
$cache_bust = '';
if (!empty($leader_profile_pic)) {
    $fullPath = __DIR__ . '/uploads/' . $leader_profile_pic;
    if (file_exists($fullPath) && is_readable($fullPath)) {
        $leader_img = 'uploads/' . $leader_profile_pic;
        $cache_bust = '?v=' . filemtime($fullPath);
    }
}
?>

<div style="width:100%; height:50px; background:#1a73e8; position:relative; color:white; font-family:sans-serif;">
    <span style="position:absolute; top:14px; font-weight:bold; right:55px; font-size:14px;">
        <?= htmlspecialchars($project['project_leader']) ?>
    </span>

    <img src="<?= htmlspecialchars($leader_img . $cache_bust) ?>"
         alt=""
         style="width:40px; height:40px; border-radius:50%; position:absolute; top:1px; right:2px; object-fit:cover;">
</div>

        <div class="page">
            <div class="tabs-wrap">
                <div class="tab-buttons" role="tablist">
                    <button type="button" onclick="showTab('tab0', this)">project details</button>
                    <button type="button" onclick="showTab('tab1', this)">Assign Employee</button>
                    <button type="button" onclick="showTab('tab2', this)">Assign Task</button>
                    <button type="button" onclick="showTab('tab3', this)">Assign Sub-Leader</button>
                    <button type="button" onclick="showTab('tab4', this)">Project Summary & Goals</button>
                </div>
            </div>

            <div id="tab0" class="tab-content card active">
                <h3>📑 Project Information</h3>
                <p><strong>Project ID:</strong> <?= intval($project['project_number']) ?></p>
                <p><strong>Project Name:</strong> <?= htmlspecialchars($project['project_name']) ?></p>
                <p><strong>Leader:</strong> <?= htmlspecialchars($project['project_leader']) ?></p>
                <p><strong>Proposal Date:</strong> <?= htmlspecialchars($project['project_proposal']) ?></p>
                <p><strong>Sanction Date:</strong> <?= htmlspecialchars($project['project_sanction']) ?></p>
                <p><strong>Completion Date:</strong> <?= htmlspecialchars($project['project_completed']) ?></p>
                <p><strong>Objective:</strong> <?= htmlspecialchars($project['project_objective']) ?></p>
                <p><strong>Project Cost:</strong> <?= $project['project_cost'] !== null ? "₹".number_format($project['project_cost'],2) : "Not Specified" ?></p>

<?php
$goal_total = $conn->query("SELECT COUNT(*) as cnt FROM project_goals WHERE project_id = $project_id")->fetch_assoc()['cnt'];
$goal_completed = $conn->query("SELECT COUNT(*) as cnt FROM project_goals WHERE project_id = $project_id AND is_completed = 1")->fetch_assoc()['cnt'];
$total_emps = $conn->query("SELECT COUNT(*) as cnt FROM lgntable WHERE usertype = 'employee'")->fetch_assoc()['cnt'];
$emps_on_project = $conn->query("SELECT COUNT(*) as cnt FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = $project_id AND l.usertype = 'employee'")->fetch_assoc()['cnt'];
?>

                <div style="display:flex; gap:50px; justify-content:center; margin-top:30px; flex-wrap:wrap;">
                    <div class="chart-container">
                        <h4 style="text-align:center; margin:6px 0 10px 0; color:#003366;">Goals Progress</h4>
                        <canvas id="goalsChart" aria-label="Goals progress chart" role="img" style="width:100%;"></canvas>
                        <p style="text-align:center; margin:8px 0 0 0;"><small>Total goals: <strong><?= intval($goal_total) ?></strong> • Completed: <strong><?= intval($goal_completed) ?></strong></small></p>
                    </div>

                    <div class="chart-container">
                        <h4 style="text-align:center; margin:6px 0 10px 0; color:#003366;">Employees on Project</h4>
                        <canvas id="employeesChart" aria-label="Employees on project chart" role="img" style="width:100%;"></canvas>
                        <p style="text-align:center; margin:8px 0 0 0;"><small>On project: <strong><?= intval($emps_on_project) ?></strong> • Total employees: <strong><?= intval($total_emps) ?></strong></small></p>
                    </div>
                </div>
            </div>

            <!-- Assign Employee -->
            <div id="tab1" class="tab-content card" style="display:none;">
                <h3>👥 Assign Employee</h3>
                <form method="POST" style="margin-bottom:15px;">
                    <select name="employee_id" required>
                        <?php
                        $emps = $conn->query("SELECT id, username FROM lgntable WHERE usertype = 'employee'");
                        while ($emp = $emps->fetch_assoc()):
                        ?>
                            <option value="<?= intval($emp['id']) ?>"><?= htmlspecialchars($emp['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="assign" class="small-btn">Assign</button>
                </form>

                <ul>
                    <?php
                    $assigned = $conn->query("SELECT a.id, l.username, l.profile_pic FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = $project_id");
                    while ($emp = $assigned->fetch_assoc()):
                        $picPath = (!empty($emp['profile_pic']) && file_exists(__DIR__.'/uploads/'.$emp['profile_pic'])) ? 'uploads/'.$emp['profile_pic'] : 'uploads/default.png';
                        $picCache = file_exists(__DIR__.'/'.$picPath) ? '?v=' . filemtime(__DIR__.'/'.$picPath) : '';
                    ?>
                        <li>
                            <span><?= htmlspecialchars($emp['username']) ?></span>
                            <a href="?project_id=<?= $project_id ?>&remove_assignment=<?= $emp['id'] ?>" style="color:red; margin-left:10px;">[Remove]</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Assign Task (Status removed from UI) -->
            <div id="tab2" class="tab-content card" style="display:none;">
                <h3>📝 Assign Task</h3>

                <?php
                // Employees assigned to this project (for dropdown)
                $empsStmt = $conn->prepare("SELECT l.id, l.username FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = ? ORDER BY l.username");
                $empsStmt->bind_param("i", $project_id);
                $empsStmt->execute();
                $empsOnProject = $empsStmt->get_result();
                ?>

                <form method="POST" style="margin-bottom:18px;">
                    <div style="display:flex; flex-wrap:wrap; gap:8px; align-items:flex-start;">
                        <div>
                            <label>Employee</label><br>
                            <select name="employee_id" required>
                                <?php while ($e = $empsOnProject->fetch_assoc()): ?>
                                    <option value="<?= intval($e['id']) ?>"><?= htmlspecialchars($e['username']) ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div style="flex:1; min-width:240px;">
                            <label>Task Title</label><br>
                            <input type="text" name="task_title" required style="width:100%;">
                        </div>
                        <div style="flex-basis:100%;"></div>
                        <div style="flex:1; min-width:280px;">
                            <label>Description</label><br>
                            <textarea name="task_description" required></textarea>
                        </div>
                        <div>
                            <label>Priority</label><br>
                            <select name="priority">
                                <option>Low</option>
                                <option selected>Medium</option>
                                <option>High</option>
                            </select>
                        </div>
                        <!-- Status control removed from UI -->
                        <div>
                            <label>Due Date</label><br>
                            <input type="date" name="due_date">
                        </div>
                        <div style="align-self:flex-end;">
                            <button type="submit" name="add_task" class="small-btn">Add Task</button>
                        </div>
                    </div>
                </form>

                <?php $empsStmt->close(); ?>

                <?php
                // Tasks list for this project
                $tasksStmt = $conn->prepare("
                    SELECT t.*, l.username
                    FROM tasks t
                    JOIN lgntable l ON t.employee_id = l.id
                    WHERE t.project_id = ?
                    ORDER BY t.created_at DESC, t.task_id DESC
                ");
                $tasksStmt->bind_param("i", $project_id);
                $tasksStmt->execute();
                $tasks = $tasksStmt->get_result();
                ?>

                <h4 style="margin:6px 0 10px 0;">All Tasks</h4>
                <table class="tasks">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Title</th>
                            <th>Description</th>
                            <th>Priority</th>
                            <th>Due</th>
                            <th>Created</th>
                            <th style="width:280px;">Update</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php while ($t = $tasks->fetch_assoc()): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['username']) ?></td>
                            <td><?= htmlspecialchars($t['task_title']) ?></td>
                            <td><?= nl2br(htmlspecialchars($t['task_description'])) ?></td>
                            <td><?= htmlspecialchars($t['priority']) ?></td>
                            <td><?= htmlspecialchars($t['due_date'] ?? '') ?></td>
                            <td><?= htmlspecialchars($t['created_at']) ?></td>
                            <td>
                                <!-- Inline edit form (status removed) -->
                                <form method="POST" style="display:flex; flex-wrap:wrap; gap:6px; align-items:center;">
                                    <input type="hidden" name="task_id" value="<?= intval($t['task_id']) ?>">
                                    <input type="text"   name="task_title" value="<?= htmlspecialchars($t['task_title']) ?>" placeholder="Title" required style="min-width:120px;">
                                    <input type="text"   name="task_description" value="<?= htmlspecialchars($t['task_description']) ?>" placeholder="Description" required style="min-width:160px;">
                                    <select name="priority">
                                        <option <?= $t['priority']=='Low'?'selected':''; ?>>Low</option>
                                        <option <?= $t['priority']=='Medium'?'selected':''; ?>>Medium</option>
                                        <option <?= $t['priority']=='High'?'selected':''; ?>>High</option>
                                    </select>
                                    <!-- status select removed -->
                                    <input type="date" name="due_date" value="<?= htmlspecialchars($t['due_date'] ?? '') ?>">
                                    <button type="submit" name="edit_task" class="small-btn">Save</button>
                                    <a class="small-btn" style="background:#c62828;" href="?project_id=<?= $project_id ?>&delete_task=<?= intval($t['task_id']) ?>" onclick="return confirm('Delete this task?')">Delete</a>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>

                <?php $tasksStmt->close(); ?>
            </div>

            <!-- Sub-Leader -->
            <div id="tab3" class="tab-content card" style="display:none;">
                <h3>⭐ Assign Sub-Leader</h3>
                <form method="POST" style="margin-bottom:15px;">
                    <select name="employee_id" required>
                        <?php
                        $emps = $conn->query("SELECT id, username FROM lgntable WHERE usertype = 'employee'");
                        while ($emp = $emps->fetch_assoc()):
                        ?>
                            <option value="<?= intval($emp['id']) ?>"><?= htmlspecialchars($emp['username']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" name="assign_subleader" class="small-btn">Make Sub-Leader</button>
                </form>

                <ul>
                    <?php
                    $subleaders = $conn->query("SELECT sl.id, l.username, l.profile_pic FROM sub_leaders sl JOIN lgntable l ON sl.employee_id = l.id WHERE sl.project_id = $project_id");
                    while ($sub = $subleaders->fetch_assoc()):
                        $picPath = (!empty($sub['profile_pic']) && file_exists(__DIR__.'/uploads/'.$sub['profile_pic'])) ? 'uploads/'.$sub['profile_pic'] : 'uploads/default.png';
                        $picCache = file_exists(__DIR__.'/'.$picPath) ? '?v=' . filemtime(__DIR__.'/'.$picPath) : '';
                    ?>
                        <li>
                            <span><?= htmlspecialchars($sub['username']) ?></span>
                            <a href="?project_id=<?= $project_id ?>&remove_subleader=<?= $sub['id'] ?>" style="color:red; margin-left:10px;">[Remove]</a>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>

            <!-- Summary & Goals -->
            <div id="tab4" class="tab-content card" style="display:none;">
                <?php
                $emp_count = $conn->query("SELECT COUNT(*) as cnt FROM assignments WHERE project_id = $project_id")->fetch_assoc()['cnt'];
                $goal_data = $conn->query("SELECT * FROM project_goals WHERE project_id = $project_id");
                $total = $goal_data->num_rows;
                $completed = 0;
                while ($g = $goal_data->fetch_assoc()) { if ($g['is_completed']) $completed++; }
                $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
                ?>
                <h3>📊 Project Summary</h3>
                <p><strong>Employees:</strong> <?= $emp_count ?></p>
                <p><strong>Goals:</strong> <?= $total ?></p>
                <p><strong>Completed:</strong> <?= $completed ?></p>

                <div style="display:flex; gap:50px; justify-content:center; margin-top:10px; flex-wrap:wrap;">
                    <div class="chart-container" style="max-width:360px;">
                        <h4 style="text-align:center; margin:6px 0 10px 0; color:#003366;">Goals Progress</h4>
                        <canvas id="goalsChartTab4" aria-label="Goals progress chart" role="img" style="width:100%; height:220px;"></canvas>
                        <p style="text-align:center; margin:8px 0 0 0;"><small>Total goals: <strong><?= intval($goal_total) ?></strong> • Completed: <strong><?= intval($goal_completed) ?></strong></small></p>
                    </div>
                </div>

                <h3>🎯 Project Goals</h3>
                <form method="POST" style="margin-bottom:10px;">
                    <input type="text" name="goal_text" placeholder="Enter new goal" required style="width:70%;">
                    <button type="submit" name="add_goal" class="small-btn">Add Goal</button>
                </form>

                <form method="POST">
                    <ul>
                        <?php
                        $goals = $conn->query("SELECT * FROM project_goals WHERE project_id = $project_id");
                        $goal_ids = [];
                        while ($goal = $goals->fetch_assoc()):
                            $goal_ids[] = $goal['id'];
                        ?>
                            <li>
                                <input type="checkbox" name="completed_goals[]" value="<?= intval($goal['id']) ?>" <?= $goal['is_completed'] ? 'checked' : '' ?>>
                                <span><?= htmlspecialchars($goal['goal_text']) ?></span>
                                <a href="?project_id=<?= $project_id ?>&remove_goal=<?= intval($goal['id']) ?>" style="color:red; margin-left:10px;">[Remove]</a>
                            </li>
                        <?php endwhile; ?>
                    </ul>

                    <?php foreach ($goal_ids as $id): ?>
                        <input type="hidden" name="goal_id[]" value="<?= intval($id) ?>">
                    <?php endforeach; ?>

                    <button type="submit" name="update_goals" class="small-btn">Update Progress</button>
                </form>
            </div>

        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        window.addEventListener('DOMContentLoaded', function () {
            var goalCompleted = <?= json_encode((int)$goal_completed) ?>;
            var goalPending = <?= json_encode((int)max($goal_total - $goal_completed, 0)) ?>;
            var empsOnProject = <?= json_encode((int)$emps_on_project) ?>;
            var totalEmps = <?= json_encode((int)$total_emps) ?>;
            var empsNotOnProject = Math.max(totalEmps - empsOnProject, 0);

            window.goalsChartInstance = null;
            window.employeesChartInstance = null;
            window.goalsChartTab4Instance = null;

            var ctxG = document.getElementById('goalsChart');
            if (ctxG) {
                window.goalsChartInstance = new Chart(ctxG.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Pending'],
                        datasets: [{ data: [goalCompleted, goalPending], backgroundColor: ['#4CAF50', '#E0E0E0'], hoverOffset: 6 }]
                    },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
                });
            }

            var ctxE = document.getElementById('employeesChart');
            if (ctxE) {
                window.employeesChartInstance = new Chart(ctxE.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['On Project', 'Not on Project'],
                        datasets: [{ data: [empsOnProject, empsNotOnProject], backgroundColor: ['#2196F3', '#E0E0E0'], hoverOffset: 6 }]
                    },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
                });
            }

            var ctxG4 = document.getElementById('goalsChartTab4');
            if (ctxG4) {
                window.goalsChartTab4Instance = new Chart(ctxG4.getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Completed', 'Pending'],
                        datasets: [{ data: [goalCompleted, goalPending], backgroundColor: ['#4CAF50', '#E0E0E0'], hoverOffset: 6 }]
                    },
                    options: { responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'bottom' } } }
                });
            }

            window.updateCharts = function() {
                try {
                    if (window.goalsChartInstance) { window.goalsChartInstance.resize(); window.goalsChartInstance.update(); }
                    if (window.employeesChartInstance) { window.employeesChartInstance.resize(); window.employeesChartInstance.update(); }
                    if (window.goalsChartTab4Instance) { window.goalsChartTab4Instance.resize(); window.goalsChartTab4Instance.update(); }
                } catch (e) { }
            };

            if (typeof window.updateCharts === 'function') {
                setTimeout(window.updateCharts, 120);
            }
        });
    </script>

</body>
</html>
