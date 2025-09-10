<?php

$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();
if(!isset($_SESSION["username"])){
  header("location:home.php");
  exit;
}

$username = $_SESSION["username"];

// Get employee id from lgntable
$stmt = $conn->prepare("SELECT id FROM lgntable WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->bind_result($employeeId);
$stmt->fetch();
$stmt->close();

if (empty($employeeId)) {
    die("Error: No employee ID found for user '$username'.");
}

// Fetch tasks from 'tasks' table
$stmt = $conn->prepare("SELECT task_title, task_description, due_date 
                        FROM tasks 
                        WHERE employee_id = ?");
$stmt->bind_param("i", $employeeId);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Tasks</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        /* Page background and basic fonts */
        body {
            font-family: Arial, sans-serif;
            background: #471396; /* purple */
            margin: 0;
            padding: 0;
        }

        /* Fixed top header (yellow bar) */
        .topbar {
            background: #FFD700; /* yellow */
            color: #000;
            height: 64px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 20px;
            box-sizing: border-box;
            border-bottom: 4px solid #3d0069; /* thin purple border to match theme */
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 999;
        }

        .topbar .brand {
            font-weight: 700;
            font-size: 20px;
        }

        .topbar .actions {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        /* Pill buttons (Back / Logout) */
        .pill {
            display: inline-block;
            padding: 8px 14px;
            background: #fff;
            color: #000;
            text-decoration: none;
            border-radius: 12px;
            font-weight: 600;
            box-shadow: 0 2px 0 rgba(0,0,0,0.08);
            border: none;
            cursor: pointer;
        }

        .pill:hover { transform: translateY(-1px); }

        /* Content wrapper pushed below fixed header */
        .page-wrap {
            margin-top: 90px; /* space to avoid header overlap */
            padding: 20px;
        }

        /* Centered white card */
        .card {
            max-width: 920px;
            margin: 0 auto;
            background: #fff;
            padding: 28px;
            border-radius: 12px;
            box-shadow: 0 6px 18px rgba(0,0,0,0.15);
        }

        h1.center-title {
            text-align: center;
            color: #471396;
            margin-top: 0;
            margin-bottom: 18px;
        }

        /* Table styles */
        table {
            border-collapse: collapse;
            width: 100%;
            background: white;
            margin-top: 8px;
        }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #4CAF50; color: white; }
        tr:nth-child(even) { background-color: #f9f9f9; }

        .no-data {
            text-align: center;
            font-weight: bold;
            color: #c12b2b;
            padding: 20px;
            background: white;
            border-radius: 6px;
        }

        /* Small responsive tweaks */
        @media (max-width: 600px) {
            .topbar .brand { font-size: 16px; }
            .pill { padding: 6px 10px; font-size: 14px; }
            .card { padding: 16px; margin: 0 12px; }
        }
    </style>
</head>
<body>
    <!-- Top yellow bar with left title and right buttons -->
    <div class="topbar">
        <div class="brand">My Tasks</div>
        <div class="actions">
            <!-- Back button (go to employee.php) -->
            <a href="employee.php" class="pill" title="Back"
   onmouseover="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor='#4CAF50'; this.style.color='#fff'; this.style.transform='translateY(-3px) scale(1.04)'; this.style.boxShadow='0 8px 18px rgba(0,0,0,0.14)';"
   onmouseout="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor=''; this.style.color=''; this.style.transform=''; this.style.boxShadow='';"
   onfocus="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor='#4CAF50'; this.style.color='#fff'; this.style.transform='translateY(-3px) scale(1.04)'; this.style.boxShadow='0 8px 18px rgba(0,0,0,0.14)';"
   onblur="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor=''; this.style.color=''; this.style.transform=''; this.style.boxShadow='';">
  Back
</a>

            <!-- Logout button (adjust target if your logout script is different) -->
            <a href="logout.php" class="pill" title="Logout"
   onmouseover="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor='#E53935'; this.style.color='#fff'; this.style.transform='translateY(-3px) scale(1.04)'; this.style.boxShadow='0 8px 18px rgba(0,0,0,0.14)';"
   onmouseout="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor=''; this.style.color=''; this.style.transform=''; this.style.boxShadow='';"
   onfocus="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor='#E53935'; this.style.color='#fff'; this.style.transform='translateY(-3px) scale(1.04)'; this.style.boxShadow='0 8px 18px rgba(0,0,0,0.14)';"
   onblur="this.style.transition='transform 180ms ease, background-color 220ms ease, box-shadow 180ms ease'; this.style.backgroundColor=''; this.style.color=''; this.style.transform=''; this.style.boxShadow='';">
  Logout
</a>

        </div>
    </div>

    <div class="page-wrap">
        <div class="card">
            <h1 class="center-title">My Tasks</h1>

            <?php if ($result->num_rows > 0): ?>
                <table>
                    <tr>
                        <th>Task Title</th>
                        <th>Description</th>
                        <th>Due Date</th>
                    </tr>
                    <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['task_title']) ?></td>
                        <td><?= htmlspecialchars($row['task_description']) ?></td>
                        <td><?= htmlspecialchars($row['due_date']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            <?php else: ?>
                <div class="no-data">No tasks assigned to you.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
<?php
$stmt->close();
$conn->close();
?>
