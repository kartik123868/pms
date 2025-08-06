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
if ($result && $result->num_rows > 0) {
    $project = $result->fetch_assoc();
} else {
    die("Project not found or access denied.");
}

// Actions
if (isset($_POST['assign'])) {
    $employee_id = $_POST['employee_id'];
    $conn->query("INSERT INTO assignments (employee_id, project_id) VALUES ($employee_id, $project_id)");
    header("Location: project_details.php?project_id=$project_id");
    exit;
}
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
if (isset($_GET['remove_goal'])) {
    $goal_id = intval($_GET['remove_goal']);
    $conn->query("DELETE FROM project_goals WHERE id = $goal_id");
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
        body {
            background-color: #3b0083;
            font-family: Arial;
            margin: 0;
            padding: 0;
            color: black;
        }
        .top-bar {
            background-color: #ffcc00;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 18px;
            font-weight: bold;
        }
        .top-bar a {
            background-color: white;
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            margin-left: 8px;
            transition: 0.3s;
        }
        .top-bar a:hover { transform: scale(1.05); }
        .tab-buttons {
            display: flex;
            justify-content: center;
            background:#3b0083;
            padding: 12px;
            border-bottom: 3px solid #ffcc00;
        }
        .tab-buttons button {
            background: white;
            border: none;
            padding: 12px 20px;
            margin: 0 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            border-radius: 5px;
            transition: 0.3s;
        }
        .tab-buttons button:hover {
            background: green;
            color: white;
            transform: scale(1.08);
        }
        .tab-content {
            display: none;
            background: white;
            color: black;
            padding: 20px;
            margin: 20px auto;
            width: 90%;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            font-size: 16px;
        }
        .active { display: block; }
        ul { list-style: none; padding-left: 0; }
        li { margin-bottom: 8px; }
    </style>
    <script>
        function showTab(tabId) {
            var tabs = document.getElementsByClassName('tab-content');
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove('active');
            }
            document.getElementById(tabId).classList.add('active');
        }
    </script>
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    Project: <?= htmlspecialchars($project['project_name']) ?>
    <div>
        <a href="projectleader.php" onmouseover="this.style.backgroundColor='green';this.style.color='white';" onmouseout="this.style.backgroundColor='white';this.style.color='black';">Back</a>
        <a href="logout.php" onmouseover="this.style.backgroundColor='red';this.style.color='white';" onmouseout="this.style.backgroundColor='white';this.style.color='black';">Logout</a>
    </div>
</div>

<!-- Tab Buttons -->
<div class="tab-buttons">
    <button onclick="showTab('tab0')">Project Details</button>
    <button onclick="showTab('tab1')">Assign Employee</button>
    <button onclick="showTab('tab2')">Assign Task</button>
    <button onclick="showTab('tab3')">Assign Sub-Leader</button>
    <button onclick="showTab('tab4')">Project Summary & Goals</button>
</div>

<!-- TAB 0: Project Details -->
<div id="tab0" class="tab-content active">
    <h3>📑 Project Information</h3>
    <p><strong>Project ID:</strong> <?= $project['project_number'] ?></p>
    <p><strong>Project Name:</strong> <?= htmlspecialchars($project['project_name']) ?></p>
    <p><strong>Leader:</strong> <?= $project['project_leader'] ?></p>
    <p><strong>Proposal Date:</strong> <?= $project['project_proposal'] ?></p>
    <p><strong>Sanction Date:</strong> <?= $project['project_sanction'] ?></p>
    <p><strong>Completion Date:</strong> <?= $project['project_completed'] ?></p>
    <p><strong>Objective:</strong> <?= htmlspecialchars($project['project_objective']) ?></p>
    <p><strong>Project Cost:</strong> <?= $project['project_cost'] !== null ? "₹".number_format($project['project_cost'],2) : "Not Specified" ?></p>
</div>

<!-- TAB 1: Assign Employee -->
<div id="tab1" class="tab-content">
    <h3>👥 Assign Employee</h3>
    <form method="POST" style="margin-bottom:15px;">
        <select name="employee_id" required style="padding:8px;">
            <?php
            $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
            while ($emp = $emps->fetch_assoc()):
            ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="assign">Assign</button>
    </form>
    <ul>
        <?php
        $assigned = $conn->query("SELECT a.id, l.username FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = $project_id");
        while ($emp = $assigned->fetch_assoc()):
        ?>
            <li><?= $emp['username'] ?> 
                <a href="?project_id=<?= $project_id ?>&remove_assignment=<?= $emp['id'] ?>" style="color:red;">[Remove]</a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<!-- TAB 2: Assign Task -->
<div id="tab2" class="tab-content">
    <h3>📝 Assign Task</h3>
    <form method="POST">
        <select name="assign_id" required style="padding:8px;">
            <?php
            $assigned = $conn->query("SELECT a.id, l.username FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = $project_id");
            while ($emp = $assigned->fetch_assoc()):
            ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
            <?php endwhile; ?>
        </select>
        <input type="text" name="task_description" placeholder="Enter task description" required style="padding:8px; width:300px;">
        <button type="submit" name="update_task">Assign Task</button>
    </form>
    <ul>
        <?php
        $tasks = $conn->query("SELECT a.id, l.username, a.task_description FROM assignments a JOIN lgntable l ON a.employee_id = l.id WHERE a.project_id = $project_id AND a.task_description IS NOT NULL AND a.task_description <> ''");
        while ($task = $tasks->fetch_assoc()):
        ?>
            <li><strong><?= $task['username'] ?>:</strong> <?= htmlspecialchars($task['task_description']) ?></li>
        <?php endwhile; ?>
    </ul>
</div>

<!-- TAB 3: Assign Sub-Leader -->
<div id="tab3" class="tab-content">
    <h3>⭐ Assign Sub-Leader</h3>
    <form method="POST">
        <select name="employee_id" required style="padding:8px;">
            <?php
            $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
            while ($emp = $emps->fetch_assoc()):
            ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
            <?php endwhile; ?>
        </select>
        <button type="submit" name="assign_subleader">Make Sub-Leader</button>
    </form>
    <ul>
        <?php
        $subleaders = $conn->query("SELECT sl.id, l.username FROM sub_leaders sl JOIN lgntable l ON sl.employee_id = l.id WHERE sl.project_id = $project_id");
        while ($sub = $subleaders->fetch_assoc()):
        ?>
            <li><?= $sub['username'] ?> 
                <a href="?project_id=<?= $project_id ?>&remove_subleader=<?= $sub['id'] ?>" style="color:red;">[Remove]</a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

<!-- TAB 4: Project Summary & Goals -->
<div id="tab4" class="tab-content">
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
    <div style="margin:15px auto; width:150px; height:150px; border-radius:50%; background:conic-gradient(green <?= $progress ?>%, #ddd 0); display:flex; align-items:center; justify-content:center; font-size:22px; font-weight:bold;">
        <?= $progress ?>%
    </div>

    <h3>🎯 Project Goals</h3>
    <form method="POST" style="margin-bottom:10px;">
        <input type="text" name="goal_text" placeholder="Enter new goal" required style="padding:8px; width:70%;">
        <button type="submit" name="add_goal">Add Goal</button>
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
                    <input type="checkbox" name="completed_goals[]" value="<?= $goal['id'] ?>" <?= $goal['is_completed'] ? 'checked' : '' ?>>
                    <?= htmlspecialchars($goal['goal_text']) ?>
                    <a href="?project_id=<?= $project_id ?>&remove_goal=<?= $goal['id'] ?>" style="color:red;">[Remove]</a>
                </li>
            <?php endwhile; ?>
        </ul>
        <?php foreach ($goal_ids as $id): ?>
            <input type="hidden" name="goal_id[]" value="<?= $id ?>">
        <?php endforeach; ?>
        <button type="submit" name="update_goals">Update Progress</button>
    </form>
</div>

</body>
</html>
