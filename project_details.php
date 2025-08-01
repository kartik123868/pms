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

$result = $conn->query("SELECT * FROM project WHERE project_number = $project_id AND project_leader = '$username'");
if ($result->num_rows == 0) {
    die("Project not found or access denied.");
}
$project = $result->fetch_assoc();


// ✅ Assign Employee (only)
if (isset($_POST['assign'])) {
    $employee_id = $_POST['employee_id'];
    $conn->query("INSERT INTO assignments (employee_id, project_id) VALUES ($employee_id, $project_id)");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}

// ✅ Remove Assigned Employee
if (isset($_GET['remove_assignment'])) {
    $assign_id = $_GET['remove_assignment'];
    $conn->query("DELETE FROM assignments WHERE id = $assign_id");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}


if (isset($_POST['update_task'])) {
    $assign_id = $_POST['assign_id'];
    $task_desc = $conn->real_escape_string($_POST['task_description']);
    $conn->query("UPDATE assignments SET task_description = '$task_desc' WHERE id = $assign_id");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}


if (isset($_POST['assign_subleader'])) {
    $employee_id = $_POST['employee_id'];
    $conn->query("INSERT INTO sub_leaders (project_id, employee_id) VALUES ($project_id, $employee_id)");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}


if (isset($_GET['remove_subleader'])) {
    $sub_id = $_GET['remove_subleader'];
    $conn->query("DELETE FROM sub_leaders WHERE id = $sub_id");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}


if (isset($_POST['add_goal'])) {
    $goal_text = $_POST['goal_text'];
    $conn->query("INSERT INTO project_goals (project_id, goal_text, is_completed) VALUES ($project_id, '$goal_text', 0)");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}


if (isset($_POST['update_goals'])) {
    foreach ($_POST['goal_id'] as $goal_id) {
        $conn->query("UPDATE project_goals SET is_completed = 0 WHERE id = $goal_id");
    }
    if (!empty($_POST['completed_goals'])) {
        foreach ($_POST['completed_goals'] as $completed_id) {
            $conn->query("UPDATE project_goals SET is_completed = 1 WHERE id = $completed_id");
        }
    }
    header("Location: project_details.php?project_id=$project_id");
    exit;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Project Details</title>
    <link rel="stylesheet" href="project_details.css">
</head>
<body>

<div class="top-bar">
    <div class="left-title">Project Details: <?= htmlspecialchars($project['project_objective']) ?></div>
    <div>
        <a href="projectleader.php" 
   style="background-color: white; 
          color: black; 
          padding: 8px 16px; 
          text-decoration: none; 
          border-radius: 5px; 
          font-weight: normal; 
          transition: all 0.2s ease;"
   onmouseover="this.style.backgroundColor='green'; this.style.color='white'; this.style.transform='scale(1.05)';"
   onmouseout="this.style.backgroundColor='white'; this.style.color='black'; this.style.transform='scale(1)';">
   Back
</a>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div style="margin:20px; background:white; color:black; padding:20px; border-radius:10px;">
    <p><strong>Project ID:</strong> <?= $project['project_number'] ?></p>
    <p><strong>Leader:</strong> <?= $project['project_leader'] ?></p>
    <p><strong>Proposal Date:</strong> <?= $project['project_proposal'] ?></p>
    <p><strong>Sanction Date:</strong> <?= $project['project_sanction'] ?></p>
    <p><strong>Completion Date:</strong> <?= $project['project_completed'] ?></p>
    <p><strong>Objective:</strong> <?= htmlspecialchars($project['project_objective']) ?></p>
</div>


<form method="POST">
    <strong>Assign Employee:</strong><br>
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <select name="employee_id" required>
        <?php
        $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
        while ($emp = $emps->fetch_assoc()):
        ?>
        <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit" name="assign" 
    style="background-color: #FFCC00; 
           color: black; 
           padding: 6px 12px; 
           border: none; 
           border-radius: 4px; 
           cursor: pointer; 
           font-weight: bold;"
    onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.backgroundColor='#FFCC00'; this.style.transform='scale(1)';">
    Assign
</button>
</form>

<ul>
<?php
$assigned = $conn->query("SELECT a.id, l.username FROM assignments a 
                          JOIN lgntable l ON a.employee_id = l.id 
                          WHERE a.project_id = $project_id");
while ($emp = $assigned->fetch_assoc()):
?>
    <li><?= $emp['username'] ?> 
        <a href="?project_id=<?= $project_id ?>&remove_assignment=<?= $emp['id'] ?>" 
           style="color: yellow; text-decoration: none; font-weight: bold; padding: 3px 6px; border: 1px solid yellow; border-radius: 4px; margin-left: 5px;">
           [Remove]
        </a>
    </li>
<?php endwhile; ?>
</ul>

<hr>


<form method="POST">
    <strong>Assign Task to Employee:</strong><br>
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <select name="assign_id" required>
        <?php
        $assigned = $conn->query("SELECT a.id, l.username FROM assignments a 
                                  JOIN lgntable l ON a.employee_id = l.id 
                                  WHERE a.project_id = $project_id");
        while ($emp = $assigned->fetch_assoc()):
        ?>
        <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
        <?php endwhile; ?>
    </select>
    <input type="text" name="task_description" placeholder="Enter task description" style="width:250px;" required>
    <button type="submit" name="update_task" 
    style="background-color:#FFCC00; 
           color:black; 
           padding:6px 12px; 
           border:none; 
           border-radius:4px; 
           cursor:pointer; 
           font-weight:bold;"
    onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.backgroundColor='#FFCC00'; this.style.transform='scale(1)';">
    Assign Task
</button>
</form>


<ul>
<?php
$tasks = $conn->query("SELECT a.id, l.username, a.task_description FROM assignments a 
                       JOIN lgntable l ON a.employee_id = l.id 
                       WHERE a.project_id = $project_id AND a.task_description IS NOT NULL AND a.task_description <> ''");
while ($task = $tasks->fetch_assoc()):
?>
    <li>
        <strong><?= $task['username'] ?>:</strong> <?= htmlspecialchars($task['task_description']) ?>
        <form method="POST" style="display:inline;">
            <input type="hidden" name="assign_id" value="<?= $task['id'] ?>">
            <input type="text" name="task_description" value="<?= htmlspecialchars($task['task_description']) ?>" style="width:200px;">
            <button type="submit" name="update_task" 
style="background-color:#FFCC00; 
       color:black; 
       border:none; 
       padding:3px 6px; 
       cursor:pointer; 
       transition: all 0.2s ease;"
onmouseover="this.style.backgroundColor='green'; this.style.color='white'; this.style.transform='scale(1.1)';"
onmouseout="this.style.backgroundColor='#FFCC00'; this.style.color='black'; this.style.transform='scale(1)';">
Update
</button>

        </form>
    </li>
<?php endwhile; ?>
</ul>

<hr>

<!-- Sub Leaders -->
<form method="POST">
    <strong>Assign Sub-Leader:</strong>
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <select name="employee_id">
        <?php
        $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
        while ($emp = $emps->fetch_assoc()):
        ?>
        <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
        <?php endwhile; ?>
    </select>
    <button type="submit" name="assign_subleader" 
    style="background-color: #FFCC00; 
           color: black; 
           padding: 6px 12px; 
           border: none; 
           border-radius: 4px; 
           cursor: pointer; 
           font-weight: bold;"
    onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.backgroundColor='#FFCC00'; this.style.transform='scale(1)';">
    Make Sub-Leader
</button>
</form>

<ul>
<?php
$subleaders = $conn->query("SELECT sl.id, l.username FROM sub_leaders sl 
                            JOIN lgntable l ON sl.employee_id = l.id 
                            WHERE sl.project_id = $project_id");
while ($sub = $subleaders->fetch_assoc()):
?>
    <li><?= $sub['username'] ?> 
        <a href="?project_id=<?= $project_id ?>&remove_subleader=<?= $sub['id'] ?>" 
   style="color: yellow; text-decoration: none; font-weight: bold; padding: 3px 6px; border: 1px solid yellow; border-radius: 4px; margin-left: 5px; display:inline-block;">
   [Remove]
</a>
    </li>
<?php endwhile; ?>
</ul>

<hr>

<!-- Goals -->
<form method="POST">
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <input type="text" name="goal_text" placeholder="Enter new goal" required>
    <button type="submit" name="add_goal" 
    style="background-color:#FFCC00; 
           color: black; 
           padding: 6px 12px; 
           border: none; 
           border-radius: 4px; 
           cursor: pointer; 
           font-weight: bold;"
    onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.backgroundColor='#FFCC00'; this.style.transform='scale(1)';">
    Add Goal
</button>
</form>

<form method="POST">
    <input type="hidden" name="project_id" value="<?= $project_id ?>">
    <ul>
        <?php
        $goals = $conn->query("SELECT * FROM project_goals WHERE project_id = $project_id");
        $total = $goals->num_rows;
        $completed = 0;
        $goal_ids = [];
        while ($goal = $goals->fetch_assoc()):
            $goal_ids[] = $goal['id'];
            if ($goal['is_completed']) $completed++;
        ?>
            <li>
                <input type="checkbox" name="completed_goals[]" value="<?= $goal['id'] ?>" <?= $goal['is_completed'] ? 'checked' : '' ?>>
                <?= htmlspecialchars($goal['goal_text']) ?>
            </li>
        <?php endwhile; ?>
    </ul>
    <?php foreach ($goal_ids as $id): ?>
        <input type="hidden" name="goal_id[]" value="<?= $id ?>">
    <?php endforeach; ?>
   <button type="submit" name="update_goals" 
    style="background-color:#FFCC00; 
           color: black; 
           padding: 6px 12px; 
           border: none; 
           border-radius: 4px; 
           cursor: pointer; 
           font-weight: bold;"
    onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.1)';"
    onmouseout="this.style.backgroundColor='#FFCC00'; this.style.transform='scale(1)';">
    Update Progress
</button>
</form>

<?php
$progress = $total > 0 ? round(($completed / $total) * 100) : 0;
?>
<div class="progress-container" style="margin:20px 0;">
    <div class="progress-bar" style="width: <?= $progress ?>%;"><?= $progress ?>%</div>
</div>

</body>
</html>
