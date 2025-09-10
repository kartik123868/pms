<?php
session_start();
$conn = mysqli_connect("localhost", "root", "", "logindetails_db");

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

$username = $_SESSION["username"];
$message = "";

// escape username
$username_esc = mysqli_real_escape_string($conn, $username);

// Get employee ID
$getEmployee = "SELECT id FROM lgntable WHERE username = '$username_esc'";
$empResult = mysqli_query($conn, $getEmployee);

if ($empResult && mysqli_num_rows($empResult) > 0) {
    $row = mysqli_fetch_assoc($empResult);
    $employee_id = $row['id'];
} else {
    echo "<p style='color: red;'>Error: Could not find employee with username '" . htmlspecialchars($username) . "'</p>";
    exit;
}

// Handle report submission
if (isset($_POST['submit'])) {
    $project_id = $_POST['project_id'];
    $report_text = mysqli_real_escape_string($conn, $_POST['report_text']);

    $insert = "INSERT INTO project_reports (project_id, employee_id, report_text)
               VALUES ('$project_id', '$employee_id', '$report_text')";

    $message = mysqli_query($conn, $insert)
        ? "<p class='success'>Report submitted successfully!</p>"
        : "<p class='error'>Error: " . htmlspecialchars(mysqli_error($conn)) . "</p>";
}

// Select projects assigned to this employee (show project_name instead of objective)
$query = "SELECT p.project_number, p.project_name
          FROM project p 
          JOIN assignments a ON p.project_number = a.project_id
          WHERE a.employee_id = $employee_id
          ORDER BY p.project_number DESC";

$result = mysqli_query($conn, $query);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Submit Project Contribution</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #4B0082;
            margin: 0;
            padding: 0;
        }
        .top-bar {
            background-color: #FFD700;
            color: black;
            padding: 15px 30px;
            font-size: 24px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .top-bar a {
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 16px;
            background-color: white;
            color: black;
            transition: 0.3s ease;
        }
        .top-bar a:hover {
            transform: scale(1.1);
        }
        .top-bar a[href="employee.php"]:hover {
            background-color: green;
            color: white;
        }
        .top-bar a[href="logout.php"]:hover {
            background-color: red;
            color: white;
        }

        .form-container {
            background-color: #fff;
            width: 60%;
            margin: 60px auto;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.25);
        }
        h2 {
            text-align: center;
            color: #4B0082;
        }
        form label {
            display: block;
            margin-top: 20px;
            font-weight: bold;
            color: #333;
        }
        form select,
        form textarea,
        form input[type="submit"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            margin-top: 10px;
            border-radius: 8px;
            border: 1px solid #ccc;
        }
        form input[type="submit"] {
            background-color: #4B0082;
            color: white;
            font-weight: bold;
            transition: 0.3s ease;
        }
        form input[type="submit"]:hover {
            background-color: #3b0070;
            cursor: pointer;
        }
        .success {
            color: green;
            font-weight: bold;
            text-align: center;
        }
        .error {
            color: red;
            font-weight: bold;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div>Submit Report</div>
        <div>
            <a href="employee.php">Back</a>
            <a href="logout.php">Logout</a>
        </div>
    </div>

    <div class="form-container">
        <h2>Submit Your Project Contribution</h2>
        <?= $message ?>

        <form method="POST" action="submit_report.php">
            <label>Select Project:</label>
            <select name="project_id" required>
                <option value="">-- Select Project --</option>
                <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                    <option value="<?= htmlspecialchars($row['project_number']) ?>">
                        <?= htmlspecialchars($row['project_number']) ?> - <?= htmlspecialchars($row['project_name'] ?: 'Untitled') ?>
                    </option>
                <?php } ?>
            </select>

            <label>Describe Your Contribution:</label>
            <textarea name="report_text" rows="6" required></textarea>

            <br>
            <input type="submit" name="submit" value="Submit Report">
        </form>
    </div>
</body>
</html>
