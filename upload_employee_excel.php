<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// DB Connection
$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// PhpSpreadsheet
require 'vendor/autoload.php';
use PhpOffice\PhpSpreadsheet\IOFactory;

$message = "";
if (isset($_POST['upload'])) {
    $fileName = $_FILES['excel_file']['tmp_name'];

    if (empty($fileName)) {
        $message = "Please choose an Excel file.";
    } else {
        $spreadsheet = IOFactory::load($fileName);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        $inserted = 0;
        $failed = 0;

        // Skip the first row if it's header
        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            list(
                $employee_id, $role, $designation, $joining_date, $retiring_date,
                $address, $contact_number, $aadhaar_number, $education,
                $pay_level, $promotion, $transfer, $emp_group
            ) = $row;

            // Sanitize inputs (basic)
            $employee_id = (int) $employee_id;

            // Check if this employee ID exists in lgntable
            $check = $conn->query("SELECT id FROM lgntable WHERE id = '$employee_id'");
            if ($check->num_rows == 0) {
                $failed++;
                continue;
            }

            // Insert into employee_details
            $stmt = $conn->prepare("INSERT INTO employee_details 
                (employee_id, role, designation, joining_date, retiring_date, address, contact_number, aadhaar_number, education, pay_level, promotion, transfer, emp_group)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

            if ($stmt) {
                $stmt->bind_param("issssssssssss", $employee_id, $role, $designation, $joining_date, $retiring_date,
                    $address, $contact_number, $aadhaar_number, $education, $pay_level, $promotion, $transfer, $emp_group);

                if ($stmt->execute()) {
                    $inserted++;
                } else {
                    $failed++;
                }
                $stmt->close();
            } else {
                $failed++;
            }
        }

        $message = "$inserted rows imported successfully.<br>$failed rows failed (invalid data or foreign key).";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Upload Excel - Employee Details</title>
    <link rel="stylesheet" href="upload_employee_excel.css">
</head>
<body>
    <div class="top-bar">
        <div class="logo">PULSEWORK</div>
        <a href="logout.php" class="logout-btn">Logout</a>
        <a href="employee.php" class="back-btn">Back</a>
    </div>

    <div class="container">
        <h2>Upload Excel to Add Employee Details</h2>
        <?php if ($message): ?>
            <p><strong><?= $message ?></strong></p>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label>Select Excel File:</label><br>
            <input type="file" name="excel_file" accept=".xlsx, .xls" required>
            <br><br>
            <button type="submit" name="upload">Upload</button>
        </form>
    </div>
</body>
</html>