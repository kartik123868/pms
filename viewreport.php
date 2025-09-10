<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "logindetails_db");

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"];
$userRes = mysqli_query($conn, "SELECT * FROM lgntable WHERE username = '$username'");
$userData = mysqli_fetch_assoc($userRes);
$userId = $userData['id'];
$usertype = $userData['usertype'];

if ($usertype != 'projectleader') {
    echo "<p style='color: red;'>Access Denied. Only project leaders can view this page.</p>";
    exit;
}

$projects = mysqli_query($conn, "SELECT * FROM project WHERE project_leader = '$username'");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Project Reports</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #471396;
            color: white;
        }

        .top-bar {
            background-color: #FFCC00;
            color: black;
            padding: 20px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left-title {
            font-size: 24px;
            font-weight: bold;
        }

        .button-group {
            display: flex;
            gap: 10px;
        }

        .back-btn, .logout-btn {
            background-color: white;
            color: black;
            padding: 10px 18px;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .back-btn:hover { background-color: green; color: white; }
        .logout-btn:hover { background-color: red; color: white; }

        .main-content {
            max-width: 1000px;
            margin: 40px auto;
            padding: 0 20px;
        }

        .project-box {
            background-color: white;
            color: black;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
        }

        .report-container {
            max-height: 150px;
            overflow-y: auto;
            background: #f9f9f9;
            border-left: 5px solid #471396;
            margin-top: 12px;
            padding: 12px;
            border-radius: 5px;
        }

        .download-btn {
            display: inline-block;
            margin-top: 10px;
            padding: 8px 12px;
            background-color: #FFCC00;
            color: black;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: all 0.3s ease;
        }

        .download-btn:hover {
            background-color: green;
            color: white;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left-title">Your Project Reports</div>
    <div class="button-group">
        <a href="projectleader.php" class="back-btn">Back</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main-content">
<?php while ($proj = mysqli_fetch_assoc($projects)) : ?>
    <div class="project-box">
        <h3>Project #<?= $proj['project_number'] ?> - <?= htmlspecialchars($proj['project_objective']) ?></h3>

        <?php
        $project_id = $proj['project_number'];
        $reports = mysqli_query($conn, "
            SELECT pr.*, lg.username 
            FROM project_reports pr 
            JOIN lgntable lg ON pr.employee_id = lg.id 
            WHERE pr.project_id = $project_id
            ORDER BY pr.id DESC
        ");

        if (mysqli_num_rows($reports) > 0) {
            echo "<div class='report-container'>";
            while ($r = mysqli_fetch_assoc($reports)) {
                echo "<div class='report'>";
                echo "<strong>By:</strong> " . htmlspecialchars($r['username']) . "<br>";
                echo "<strong>Report:</strong><br>" . nl2br(htmlspecialchars($r['report_text']));
                echo "<hr>";
                echo "</div>";
            }
            echo "</div>";
            echo "<a class='download-btn' href='generate_pdf.php?project_id=$project_id'>Download PDF</a>";
        } else {
            echo "<p><em>No reports submitted for this project yet.</em></p>";
        }
        ?>
    </div>
<?php endwhile; ?>
</div>

</body>
</html>
