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

$username = $_SESSION["username"];
$userQuery = $conn->query("SELECT * FROM lgntable WHERE username = '$username' AND usertype = 'employee'");
if ($userQuery->num_rows == 0) {
    die("Access denied. Not an employee.");
}

$userData = $userQuery->fetch_assoc();
$employeeId = $userData['id'];

$projectResult = $conn->query("
    SELECT p.* FROM project p 
    JOIN assignments a ON p.project_number = a.project_id
    WHERE a.employee_id = $employeeId
");

// 💡 Check if this employee is a sub-leader for any project
$subleaderCheck = $conn->query("SELECT project_id FROM sub_leaders WHERE employee_id = $employeeId");
$subLeaderProjects = [];
while ($row = $subleaderCheck->fetch_assoc()) {
    $subLeaderProjects[] = $row['project_id'];
}
$isSubLeader = count($subLeaderProjects) > 0;
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="employee.css">
    <style>
        .progress-container {
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
            height: 20px;
            width: 100%;
            margin-top: 5px;
        }

        .progress-bar {
            height: 100%;
            background-color: #4caf50;
            text-align: center;
            color: white;
            line-height: 20px;
            font-size: 12px;
        }

        .subleader-box {
            background: #fffbcc;
            border: 1px solid #f0c000;
            padding: 10px;
            margin: 10px 0;
            color: #333;
            font-weight: bold;
        }

        .subleader-box a {
            color: #0000ee;
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left-title">Employee Dashboard</div>
    <a href="logout.php" class="logout-btn">Logout</a>
</div>

<div class="content">

    <!-- 👇 Sub-leader message box -->
    <?php if ($isSubLeader): ?>
        <div class="subleader-box">
            <?php foreach ($subLeaderProjects as $projNo): ?>
                The project leader made you a temporary Sub Project Leader for <strong>Project No. <?= $projNo ?></strong>.<br>
            <?php endforeach; ?>
            <a href="subleader.php">Go to Sub-Leader Panel</a>
        </div>
    <?php endif; ?>

    <h2>Your Assigned Projects</h2>

    <?php if ($projectResult->num_rows > 0): ?>
        <table border="1" cellpadding="10">
            <tr>
                <th>Project No.</th>
                <th>Leader</th>
                <th>Proposal</th>
                <th>Sanction</th>
                <th>Completed</th>
                <th>Objective</th>
                <th>Progress</th>
            </tr>
            <?php while ($row = $projectResult->fetch_assoc()): ?>
                <?php
                    $projectId = $row['project_number'];
                    $goalQuery = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as done FROM project_goals WHERE project_id = $projectId");
                    $goalData = $goalQuery->fetch_assoc();
                    $totalGoals = $goalData['total'];
                    $completedGoals = $goalData['done'];
                    $progress = ($totalGoals > 0) ? round(($completedGoals / $totalGoals) * 100) : 0;
                ?>
                <tr>
                    <td><?= $row['project_number'] ?></td>
                    <td><?= $row['project_leader'] ?></td>
                    <td><?= $row['project_proposal'] ?></td>
                    <td><?= $row['project_sanction'] ?></td>
                    <td><?= $row['project_completed'] ?></td>
                    <td><?= $row['project_objective'] ?></td>
                    <td>
                        <div class="progress-container">
                            <div class="progress-bar" style="width: <?= $progress ?>%;"><?= $progress ?>%</div>
                        </div>
                    </td>
                </tr>
            <?php endwhile; ?>
        </table>
    <?php else: ?>
        <p class="no-projects">No project assigned to you.</p>
    <?php endif; ?>
</div>

</body>
</html>
