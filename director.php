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

// Check login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

// Handle Add User
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $usertype = $_POST['usertype'];

    $conn->query("INSERT INTO lgntable (username, password, usertype) VALUES ('$username', '$password', '$usertype')");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Delete User
if (isset($_GET['delete_user'])) {
    $id = $_GET['delete_user'];
    $conn->query("DELETE FROM lgntable WHERE id = $id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Project Update
if (isset($_POST['update_project'])) {
    $id = $_POST['id'];
    $leader = $_POST['leader'];
    $proposal = $_POST['proposal'];
    $sanction = $_POST['sanction'];
    $completed = $_POST['completed'];
    $objective = $_POST['objective'];

    $sql = "UPDATE project SET 
                project_leader = '$leader',
                project_proposal = '$proposal',
                project_sanction = '$sanction',
                project_completed = '$completed',
                project_objective = '$objective'
            WHERE project_number = $id";
    $conn->query($sql);
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

// Handle Project Delete
if (isset($_GET['delete_project'])) {
    $id = $_GET['delete_project'];
    $conn->query("DELETE FROM project WHERE project_number = $id");
    $conn->query("DELETE FROM assignments WHERE project_id = $id");
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$projects = $conn->query("SELECT * FROM project");
$users = $conn->query("SELECT * FROM lgntable WHERE usertype = 'projectleader' OR usertype = 'employee'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Director Dashboard</title>
    <link rel="stylesheet" href="director.css">
</head>
<body>

<div class="top-bar">
    <div class="left-title">Director Dashboard</div>
    <div class="button-group">
       
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main-content">
    <h2>All Projects</h2>
    <?php while ($p = $projects->fetch_assoc()): ?>
        <form method="POST" class="project-box">
            <input type="hidden" name="id" value="<?= $p['project_number'] ?>">
            <p><strong>Project #<?= $p['project_number'] ?></strong></p>
            <label>Leader: <input type="text" name="leader" value="<?= $p['project_leader'] ?>"></label><br>
            <label>Proposal: <input type="date" name="proposal" value="<?= $p['project_proposal'] ?>"></label><br>
            <label>Sanction: <input type="date" name="sanction" value="<?= $p['project_sanction'] ?>"></label><br>
            <label>Completed: <input type="date" name="completed" value="<?= $p['project_completed'] ?>"></label><br>
            <label>Objective: <input type="text" name="objective" value="<?= $p['project_objective'] ?>"></label><br>
            <button type="submit" name="update_project">Update</button>
            <a href="?delete_project=<?= $p['project_number'] ?>" onclick="return confirm('Delete this project?')">Delete</a>
        </form>
    <?php endwhile; ?>

    <hr>

    <h2>Manage Users</h2>
    <form method="POST" class="add-user-form">
        <label>Username: <input type="text" name="username" required></label>
        <label>Password: <input type="text" name="password" required></label>
        <label>Usertype:
            <select name="usertype">
                <option value="employee">Employee</option>
                <option value="projectleader">Project Leader</option>
            </select>
        </label>
        <button type="submit" name="add_user">Add User</button>
    </form>

    <ul class="user-list">
        <?php while ($u = $users->fetch_assoc()): ?>
            <li><?= $u['username'] ?> (<?= $u['usertype'] ?>)
                <a href="?delete_user=<?= $u['id'] ?>" onclick="return confirm('Delete this user?')">[Remove]</a>
            </li>
        <?php endwhile; ?>
    </ul>
</div>

</body>
</html>
