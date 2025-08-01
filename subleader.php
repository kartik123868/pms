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
$userQuery = $conn->query("SELECT * FROM lgntable WHERE username = '$username'");
if ($userQuery->num_rows == 0) {
    die("Access denied.");
}
$userData = $userQuery->fetch_assoc();
$employeeId = $userData['id'];

// Get all projects assigned as sub-leader
$projectQuery = $conn->query("
    SELECT p.* FROM project p 
    JOIN sub_leaders s ON p.project_number = s.project_id
    WHERE s.employee_id = $employeeId
");

$projects = [];
while ($row = $projectQuery->fetch_assoc()) {
    $projects[] = $row;
}

// Handle add goal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_goal'])) {
    $project_id = $_POST['project_id'];
    $goal_text = $conn->real_escape_string($_POST['goal_text']);
    $conn->query("INSERT INTO project_goals (project_id, goal_text, is_completed) VALUES ($project_id, '$goal_text', 0)");
    header("Location: subleader.php");
    exit;
}

// Handle mark complete
if (isset($_GET['complete']) && isset($_GET['goal_id'])) {
    $goal_id = $_GET['goal_id'];
    $conn->query("UPDATE project_goals SET is_completed = 1 WHERE id = $goal_id");
    header("Location: subleader.php");
    exit;
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['goal_id'])) {
    $goal_id = $_GET['goal_id'];
    $conn->query("DELETE FROM project_goals WHERE id = $goal_id");
    header("Location: subleader.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Sub-Leader Panel</title>
    <link rel="stylesheet" href="subleader.css">
    <style>
        .goal-box {
            background-color: white;
            color: black;
            padding: 10px;
            margin: 15px 0;
            border-radius: 5px;
        }
        .goal-box ul {
            list-style: none;
            padding: 0;
        }
        .goal-box li {
            margin: 6px 0;
        }
        .goal-actions a {
            margin-left: 10px;
            text-decoration: none;
            color: blue;
        }
        .goal-actions a:hover {
            color: red;
        }

        form.add-goal {
            margin-top: 10px;
        }
        .add-goal input[type="text"] {
            padding: 5px;
            width: 60%;
        }
        .add-goal input[type="submit"] {
            padding: 6px 14px;
            background-color: #4caf50;
            color: white;
            border: none;
            cursor: pointer;
            border-radius: 4px;
        }
        .add-goal input[type="submit"]:hover {
            background-color: #388e3c;
        }

        .top-buttons {
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
        }

        .top-buttons a {
            background-color: white;
            color: black;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            transition: 0.3s ease;
            font-weight: bold;
        }

        .top-buttons a:hover {
            background-color: red;
            color: white;
            transform: scale(1.1);
        }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="left-title">Sub Project Leader Panel</div>
    <div class="top-buttons">
        <a href="employee.php" class="back-btn">Back</a>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="content">
    <h2>Projects You Lead Temporarily</h2>

    <?php if (count($projects) > 0): ?>
        <?php foreach ($projects as $project): ?>
            <div class="goal-box">
                <h3>Project #<?= $project['project_number'] ?>: <?= $project['project_objective'] ?></h3>
                <p><strong>Leader:</strong> <?= $project['project_leader'] ?> |
                   <strong>Proposal:</strong> <?= $project['project_proposal'] ?> |
                   <strong>Sanction:</strong> <?= $project['project_sanction'] ?></p>

                <!-- Show goals -->
                <ul>
                    <?php
                        $pid = $project['project_number'];
                        $goals = $conn->query("SELECT * FROM project_goals WHERE project_id = $pid");
                        while ($goal = $goals->fetch_assoc()):
                    ?>
                        <li>
                            <?= $goal['goal_text'] ?>
                            <?= $goal['is_completed'] ? "(✔️ Completed)" : "" ?>
                            <span class="goal-actions">
                                <?php if (!$goal['is_completed']): ?>
                                    <a href="?complete=1&goal_id=<?= $goal['id'] ?>">Mark Complete</a>
                                <?php endif; ?>
                                <a href="?delete=1&goal_id=<?= $goal['id'] ?>">Delete</a>
                            </span>
                        </li>
                    <?php endwhile; ?>
                </ul>

                <!-- Add goal form -->
                <form method="post" class="add-goal">
                    <input type="hidden" name="project_id" value="<?= $pid ?>">
                    <input type="text" name="goal_text" placeholder="Enter new goal" required>
                    <input type="submit" name="add_goal" value="Add Goal">
                </form>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p class="no-projects">You are not a sub-leader on any projects currently.</p>
    <?php endif; ?>
</div>

</body>
</html>
