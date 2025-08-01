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
</head>
<body>

<div class="top-bar">
    <div class="left-title">Project Leader Dashboard</div>
    <div>
        <a href="viewreport.php" 
   style="display: inline-block; padding: 10px 20px; background-color: white; color: black; text-decoration: none; border-radius: 5px; transition: all 0.3s ease;"
   onmouseover="this.style.backgroundColor='green'; this.style.transform='scale(1.05)';"
   onmouseout="this.style.backgroundColor='white'; this.style.transform='scale(1)';">
   View Project Report
</a>

        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
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
       <a href="project_details.php?project_id=<?= $row['project_number'] ?>" >View</a>
    </td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
