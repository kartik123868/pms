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
    header("Location: home.php");
    exit;
}

$username = $_SESSION["username"];
// escape username to be safe
$username_esc = $conn->real_escape_string($username);
$userQuery = $conn->query("SELECT * FROM lgntable WHERE username = '$username_esc' AND usertype = 'employee'");
if ($userQuery->num_rows == 0) {
    die("Access denied. Not an employee.");
}

$userData = $userQuery->fetch_assoc();
$employeeId = (int)$userData['id'];

/*
 Fetch projects assigned to this employee.
 We join assignments -> project so we can get project_name and project_cost directly.
*/
$projectResult = $conn->query("
    SELECT p.*, a.task_description 
    FROM project p 
    JOIN assignments a ON p.project_number = a.project_id
    WHERE a.employee_id = $employeeId
    ORDER BY p.project_number DESC
");

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
        /* Top bar layout */
        .top-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: #FFCC00;
            padding: 12px 18px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }

        /* left title */
        .left-title {
            font-weight: 700;
            font-size: 20px;
            color: #000;
            margin-left: 6px;
        }

        /* right group (logout, view task, submit) */
        .right-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Buttons / links */
        .top-btn {
            display: inline-block;
            padding: 8px 14px;
            border-radius: 8px;
            background: white;
            color: black;
            text-decoration: none;
            font-weight: 600;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.12);
            transition: transform .12s ease, background-color .15s ease, color .15s ease;
        }
        .top-btn:hover {
            transform: translateY(-2px);
        }

        /* view-task uses same visual style as submit (white -> green on hover) */
        .view-task {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            background: white;
            color: black;
            box-shadow: 2px 2px 6px rgba(0,0,0,0.08);
            transition: transform .12s ease, background-color .12s ease, color .12s ease;
        }
        .view-task:hover {
            transform: translateY(-2px);
            background-color: #07a33a;
            color: #fff;
        }

        .submit-btn {
            background: white;
            font-weight: 700;
        }
        .submit-btn:hover {
            background: #07a33a;
            color: #fff;
        }

        /* keep the logout visually consistent but slightly simpler */
        .logout-btn {
            background: white;
        }

        /* Page content */
        .content {
            padding: 34px 24px;
            background: #47139;
            min-height: calc(100vh - 74px);
        }

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
            border-radius: 6px;
        }
        .subleader-box a {
            color: #0000ee;
            text-decoration: underline;
        }

        /* Assigned projects table */
        table.assigned-projects {
            border-collapse: collapse;
            margin: 30px auto;
            min-width: 800px;
            width: 70%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
        }
        table.assigned-projects th, table.assigned-projects td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
            vertical-align: middle;
        }
        table.assigned-projects th {
            background: #f8e6ff;
            color: #111;
            font-weight: 700;
        }
        .no-projects {
            background: #fff;
            padding: 12px;
            border-radius: 8px;
            width: fit-content;
            margin: 20px auto;
        }

        /* small screens */
        @media (max-width: 900px) {
            table.assigned-projects {
                width: 95%;
            }
            .top-bar { padding: 10px; }
            .left-title { font-size: 18px; }
            .right-actions { gap: 8px; }
        }
    </style>
</head>
<body>

<div class="top-bar">
    <!-- left: title -->
    <div class="left-title">Employee Dashboard</div>

    <!-- right controls -->
    <div class="right-actions">
        <!-- Logout first -->
        <a href="logout.php" class="top-btn logout-btn">Logout</a>

        <!-- view task next to logout (same hover/inline effect as submit) -->
        <a href="emptsk.php" class="view-task">view task</a>

        <!-- Submit Project Contribution next -->
        <a href="submit_report.php" class="top-btn submit-btn">Submit Project Contribution</a>
    </div>
</div>

<div class="content">

    <?php if ($isSubLeader): ?>
        <div class="subleader-box">
            <?php foreach ($subLeaderProjects as $projNo): ?>
                The project leader made you a temporary Sub Project Leader for <strong>Project No. <?= htmlspecialchars($projNo) ?></strong>.<br>
            <?php endforeach; ?>
            <a href="subleader.php">Go to Sub-Leader Panel</a>
        </div>
    <?php endif; ?>

    <h2 style="text-align:center; color:#fff; margin-top:6px;">Your Assigned Projects</h2>

    <?php if ($projectResult && $projectResult->num_rows > 0): ?>
        <table class="assigned-projects" cellpadding="10">
            <tr>
                <th>Project No.</th>
                <th>Project Name</th>
                <th>Leader</th>
                <th>Proposal</th>
                <th>Sanction</th>
                <th>Completed</th>
                <th>Objective</th>
                <th>Cost</th>
                <th>Progress</th>
            </tr>
            <?php while ($row = $projectResult->fetch_assoc()): ?>
                <?php
                    $projectId = (int)$row['project_number'];

                    // calculate progress based on project_goals
                    $goalQuery = $conn->query("SELECT COUNT(*) as total, SUM(CASE WHEN is_completed = 1 THEN 1 ELSE 0 END) as done FROM project_goals WHERE project_id = $projectId");
                    $goalData = $goalQuery ? $goalQuery->fetch_assoc() : ['total' => 0, 'done' => 0];
                    $totalGoals = (int)$goalData['total'];
                    $completedGoals = (int)$goalData['done'];
                    $progress = ($totalGoals > 0) ? round(($completedGoals / $totalGoals) * 100) : 0;

                    // format cost
                    $costDisplay = '—';
                    if ($row['project_cost'] !== null && $row['project_cost'] !== '') {
                        $costDisplay = '₹' . number_format((float)$row['project_cost'], 2);
                    }
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['project_number']) ?></td>
                    <td><?= htmlspecialchars($row['project_name'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['project_leader'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['project_proposal'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['project_sanction'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['project_completed'] ?: '—') ?></td>
                    <td><?= htmlspecialchars($row['project_objective'] ?: '—') ?></td>
                    <td><?= $costDisplay ?></td>
                    <td style="width:160px;">
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
