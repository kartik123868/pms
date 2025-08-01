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

// Optional: Check login
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$projects = $conn->query("SELECT * FROM project ORDER BY project_number DESC");
?>

<!DOCTYPE html>
<html>
<head>
    <title>View All Projects</title>
    <link rel="stylesheet" href="viewproject.css">
</head>
<body>

<div class="top-bar">
    <div class="left-title">All Projects</div>
    <div class="button-group">
        <a href="admin.php" class="back-btn">Back</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>


<div class="main-content">
    <?php while ($project = $projects->fetch_assoc()): ?>
        <div class="project-box">
            <h3>Project #<?= $project['project_number'] ?></h3>
            <p><strong>Leader:</strong> <?= htmlspecialchars($project['project_leader']) ?></p>
            <p><strong>Proposal Date:</strong> <?= $project['project_proposal'] ?></p>
            <p><strong>Sanction Date:</strong> <?= $project['project_sanction'] ?></p>
            <p><strong>Completion Date:</strong> <?= $project['project_completed'] ?></p>
            <p><strong>Objective:</strong> <?= htmlspecialchars($project['project_objective']) ?></p>
            <p><strong>Employees Assigned:</strong></p>
            <ul>
                <?php
                $pid = $project['project_number'];
                $assigned = $conn->query("
                    SELECT l.username FROM assignments a
                    JOIN lgntable l ON a.employee_id = l.id
                    WHERE a.project_id = $pid
                ");
                if ($assigned->num_rows > 0) {
                    while ($emp = $assigned->fetch_assoc()) {
                        echo "<li>" . htmlspecialchars($emp['username']) . "</li>";
                    }
                } else {
                    echo "<li><em>No employees assigned</em></li>";
                }
                ?>
            </ul>
        </div>
    <?php endwhile; ?>
</div>

</body>
</html>
