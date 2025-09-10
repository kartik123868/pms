<?php
session_start();

$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }

if (!isset($_SESSION["username"])) {
    header("Location: home.php");
    exit;
}

/* === Add user === */
if (isset($_POST['add_user'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $usertype = $_POST['usertype'];
    $stmt = $conn->prepare("INSERT INTO lgntable (username, password, usertype) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $password, $usertype);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* === Delete user === */
if (isset($_GET['delete_user'])) {
    $id = (int)$_GET['delete_user'];
    $stmt = $conn->prepare("DELETE FROM lgntable WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

/* === Delete project === */
if (isset($_GET['delete_project'])) {
    $pid = (int)$_GET['delete_project'];
    $alt = null;
    if ($stmt = $conn->prepare("SELECT project_id FROM project WHERE project_number=?")) {
        $stmt->bind_param("i", $pid);
        $stmt->execute();
        $stmt->bind_result($alt);
        $stmt->fetch();
        $stmt->close();
    }
    $stmt = $conn->prepare("DELETE FROM assignments WHERE project_id = ? OR project_id = ?");
    $stmt->bind_param("ii", $pid, $alt);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM sub_leaders WHERE project_id = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $stmt->close();

    $stmt = $conn->prepare("DELETE FROM project WHERE project_number = ?");
    $stmt->bind_param("i", $pid);
    $stmt->execute();
    $stmt->close();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit;
}

$project_list = $conn->query("SELECT project_number, project_name, project_cost FROM project ORDER BY project_number DESC");
$users = $conn->query("SELECT * FROM lgntable WHERE usertype IN ('projectleader','employee')");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Sub Director Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f9;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background: #003366;
            padding: 12px 20px;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar .left-title {
            font-size: 20px;
            font-weight: bold;
        }
        .top-bar a {
            color: white;
            text-decoration: none;
            padding: 8px 12px;
            background: #cc0000;
            border-radius: 6px;
        }
        .main-content {
            padding: 20px;
            max-width: 1000px;
            margin: auto;
        }
        h2 {
            color: #003366;
            margin-bottom: 10px;
        }
        .project-button-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 30px;
        }
        .project-btn {
            background-color: #fff;
            color: #000;
            padding: 15px 20px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            transition: background-color 0.3s ease, color 0.3s ease, transform 0.2s ease;
        }
        .project-btn:hover {
            background-color: #4CAF50;
            color: white;
            transform: translateY(-2px);
        }
        .project-btn .cost {
            font-weight: normal;
            font-size: 14px;
        }
        .add-user-form {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
            margin-bottom: 20px;
        }
        .add-user-form label {
            display: block;
            margin-bottom: 8px;
        }
        .add-user-form input, 
        .add-user-form select {
            padding: 8px;
            margin-left: 5px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        .add-user-form button {
            padding: 8px 14px;
            background: #003366;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .add-user-form button:hover {
            background: #0055aa;
        }
        .user-list {
            list-style: none;
            padding: 0;
        }
        .user-list li {
            padding: 8px;
            background: white;
            margin-bottom: 6px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }
        .user-list a {
            color: red;
            margin-left: 10px;
            text-decoration: none;
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left-title">Sub Director Dashboard</div>
    <div class="button-group">
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="main-content">
    <h2>All Projects</h2>
    <div class="project-button-list">
        <?php while ($row = $project_list->fetch_assoc()):
            $title = $row['project_name'] ?: ('Project #'.$row['project_number']);
            $cost  = $row['project_cost'] !== null ? number_format((float)$row['project_cost'], 2) : '—';
        ?>
            <a class="project-btn" href="p_vfrsubdir.php?pid=<?= $row['project_number'] ?>">
                <span>#<?= $row['project_number'] ?> — <?= htmlspecialchars($title) ?></span>
                <span class="cost">₹<?= $cost ?></span>
            </a>
        <?php endwhile; ?>
    </div>

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

   
</div>

</body>
</html>
