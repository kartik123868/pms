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
$userQuery = $conn->query("SELECT * FROM lgntable WHERE username = '$username' AND usertype = 'projectleader'");
if ($userQuery->num_rows == 0) {
    die("Access denied. Not a project leader.");
}

// Handle project creation
if (isset($_POST['add'])) {
    $proposal = $_POST['proposal'];
    $sanction = $_POST['sanction'];
    $completed = $_POST['completed'];
    $objective = $_POST['objective'];

    $sql = "INSERT INTO project (project_leader, project_proposal, project_sanction, project_completed, project_objective)
            VALUES ('$username', '$proposal', '$sanction', '$completed', '$objective')";
    $conn->query($sql);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle project update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $proposal = $_POST['proposal'];
    $sanction = $_POST['sanction'];
    $completed = $_POST['completed'];
    $objective = $_POST['objective'];

    $sql = "UPDATE project SET 
                project_proposal = '$proposal',
                project_sanction = '$sanction',
                project_completed = '$completed',
                project_objective = '$objective'
            WHERE project_number = $id AND project_leader = '$username'";
    $conn->query($sql);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle project deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $conn->query("DELETE FROM project WHERE project_number = $id AND project_leader = '$username'");
    $conn->query("DELETE FROM assignments WHERE project_id = $id");
    $conn->query("DELETE FROM project_goals WHERE project_id = $id");
    $conn->query("DELETE FROM sub_leaders WHERE project_id = $id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle employee assignment
if (isset($_POST['assign'])) {
    $project_id = $_POST['project_id'];
    $employee_id = $_POST['employee_id'];
    $conn->query("INSERT INTO assignments (employee_id, project_id) VALUES ($employee_id, $project_id)");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle assignment removal
if (isset($_GET['remove_assignment'])) {
    $assign_id = $_GET['remove_assignment'];
    $conn->query("DELETE FROM assignments WHERE id = $assign_id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle goal addition
if (isset($_POST['add_goal'])) {
    $project_id = $_POST['project_id'];
    $goal_text = $_POST['goal_text'];
    $conn->query("INSERT INTO project_goals (project_id, goal_text, is_completed) VALUES ($project_id, '$goal_text', 0)");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle goal update
if (isset($_POST['update_goals'])) {
    foreach ($_POST['goal_id'] as $goal_id) {
        $conn->query("UPDATE project_goals SET is_completed = 0 WHERE id = $goal_id");
    }
    if (!empty($_POST['completed_goals'])) {
        foreach ($_POST['completed_goals'] as $completed_id) {
            $conn->query("UPDATE project_goals SET is_completed = 1 WHERE id = $completed_id");
        }
    }
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Assign sub-project leader
if (isset($_POST['assign_subleader'])) {
    $project_id = $_POST['project_id'];
    $employee_id = $_POST['employee_id'];
    $conn->query("INSERT INTO sub_leaders (project_id, employee_id) VALUES ($project_id, $employee_id)");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Remove sub-project leader
if (isset($_GET['remove_subleader'])) {
    $sub_id = $_GET['remove_subleader'];
    $conn->query("DELETE FROM sub_leaders WHERE id = $sub_id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$edit = false;
$edit_data = [];
if (isset($_GET['edit'])) {
    $edit = true;
    $id = $_GET['edit'];
    $res = $conn->query("SELECT * FROM project WHERE project_number = $id AND project_leader = '$username'");
    $edit_data = $res->fetch_assoc();
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Project Leader Dashboard</title>
    <link rel="stylesheet" href="projectleader.css">
    <style>
        .progress-container {
            width: 100%;
            background-color: #ddd;
            border-radius: 20px;
            margin: 10px 0;
        }
        .progress-bar {
            height: 20px;
            background-color: green;
            border-radius: 20px;
            width: 0%;
            text-align: center;
            color: white;
        }
        .project-spacing {
            display: block;
            height: 40px;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left-title">Project Leader Dashboard</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<h2 style="text-align:center;"><?= $edit ? "Edit" : "Add" ?> Project</h2>

<form method="POST">
    <?php if ($edit): ?>
        <input type="hidden" name="id" value="<?= $edit_data['project_number'] ?>">
    <?php endif; ?>
    Project Leader: <input type="text" value="<?= $username ?>" disabled><br><br>
    Proposal Date: <input type="date" name="proposal" value="<?= $edit ? $edit_data['project_proposal'] : '' ?>" required><br><br>
    Sanction Date: <input type="date" name="sanction" value="<?= $edit ? $edit_data['project_sanction'] : '' ?>" required><br><br>
    Completion Date: <input type="date" name="completed" value="<?= $edit ? $edit_data['project_completed'] : '' ?>"><br><br>
    Objective: <input type="text" name="objective" value="<?= $edit ? $edit_data['project_objective'] : '' ?>" required><br><br>
    <input type="submit" name="<?= $edit ? 'update' : 'add' ?>" value="<?= $edit ? 'Update' : 'Add' ?> Project">
</form>

<hr>

<h2 style="text-align:center;">Your Projects</h2>

<table border="1" cellpadding="10">
<tr>
    <th>Project No.</th>
    <th>Leader</th>
    <th>Proposal</th>
    <th>Sanction</th>
    <th>Completed</th>
    <th>Objective</th>
    <th>Actions</th>
</tr>

<?php
$result = $conn->query("SELECT * FROM project WHERE project_leader = '$username'");
while ($row = $result->fetch_assoc()):
?>
<tr>
    <td><?= $row['project_number'] ?></td>
    <td><?= $row['project_leader'] ?></td>
    <td><?= $row['project_proposal'] ?></td>
    <td><?= $row['project_sanction'] ?></td>
    <td><?= $row['project_completed'] ?></td>
    <td><?= $row['project_objective'] ?></td>
    <td>
        <a href="?edit=<?= $row['project_number'] ?>">Edit</a> |
        <a href="?delete=<?= $row['project_number'] ?>" onclick="return confirm('Delete this project?');">Delete</a>
    </td>
</tr>
<tr class="project-section">
    <td colspan="7">
        <!-- All project controls go here: assign, subleaders, goals etc. -->
        <!-- (No changes needed to inner logic) -->

        <!-- 👇 this block is unchanged -->
        <form method="POST">
            <strong>Assign Employee:</strong>
            <input type="hidden" name="project_id" value="<?= $row['project_number'] ?>">
            <select name="employee_id">
                <?php
                $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
                while ($emp = $emps->fetch_assoc()):
                ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="assign">Assign</button>
        </form>

        <br><strong>Assigned Employees:</strong>
        <ul>
        <?php
        $project_id = $row['project_number'];
        $assigned = $conn->query("SELECT a.id, l.username FROM assignments a 
                                  JOIN lgntable l ON a.employee_id = l.id 
                                  WHERE a.project_id = $project_id");
        while ($emp = $assigned->fetch_assoc()):
        ?>
        <li><?= $emp['username'] ?>
            <a href="?remove_assignment=<?= $emp['id'] ?>" onclick="return confirm('Remove this employee?');">[Remove]</a>
        </li>
        <?php endwhile; ?>
        </ul>

        <hr>
        <!-- Sub Leader Assignment -->
        <form method="POST">
            <strong>Assign Sub-Project Leader:</strong>
            <input type="hidden" name="project_id" value="<?= $row['project_number'] ?>">
            <select name="employee_id">
                <?php
                $emps = $conn->query("SELECT * FROM lgntable WHERE usertype = 'employee'");
                while ($emp = $emps->fetch_assoc()):
                ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?></option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="assign_subleader">Make Sub-project-Leader</button>
        </form>

        <strong>Current Sub-Leaders:</strong>
        <ul>
        <?php
        $subleaders = $conn->query("SELECT sl.id, l.username FROM sub_leaders sl 
                                    JOIN lgntable l ON sl.employee_id = l.id 
                                    WHERE sl.project_id = $project_id");
        while ($sub = $subleaders->fetch_assoc()):
        ?>
            <li><?= $sub['username'] ?>
                <a href="?remove_subleader=<?= $sub['id'] ?>" onclick="return confirm('Remove this sub-leader?');">[Remove]</a>
            </li>
        <?php endwhile; ?>
        </ul>

        <hr>
        <strong>Project Goals:</strong><br>
        <form method="POST">
            <input type="hidden" name="project_id" value="<?= $row['project_number'] ?>">
            <input type="text" name="goal_text" placeholder="Enter new goal" required>
            <button type="submit" name="add_goal">Add Goal</button>
        </form>

        <form method="POST">
            <input type="hidden" name="project_id" value="<?= $row['project_number'] ?>">
            <ul>
                <?php
                $goals = $conn->query("SELECT * FROM project_goals WHERE project_id = {$row['project_number']}");
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
            <button type="submit" name="update_goals">Update Progress</button>
        </form>

        <?php
        $progress = $total > 0 ? round(($completed / $total) * 100) : 0;
        ?>
        <div class="progress-container">
            <div class="progress-bar" style="width: <?= $progress ?>%;"><?= $progress ?>%</div>
        </div>
    </td>
</tr>
<tr><td colspan="7"><div class="project-spacing"></div></td></tr>
<?php endwhile; ?>
</table>

</body>
</html>
