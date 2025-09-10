<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";
$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

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

        for ($i = 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            list(
                $employee_id, $role, $designation, $joining_date, $retiring_date,
                $address, $contact_number, $aadhaar_number, $education,
                $pay_level, $promotion, $transfer, $emp_group
            ) = $row;

            $employee_id = (int) $employee_id;

            $check = $conn->query("SELECT id FROM lgntable WHERE id = '$employee_id'");
            if ($check->num_rows == 0) {
                $failed++;
                continue;
            }

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
    <meta charset="utf-8">
    <title>Upload Excel - Employee Details</title>
    <style>
        :root{
            --pulse-purple:#5b2b8a;
            --top-yellow:#ffd54f;
            --card-white:#ffffff;
            --muted:#f3e8ff;
        }
        html,body{
            height:100%;
            margin:0;
            font-family: Arial, Helvetica, sans-serif;
            background: #471396;
            color:#fff;
        }
        .top-bar{
            position:fixed;
            top:0;
            left:0;
            width:100%;
            height:64px;
            background:var(--top-yellow);
            display:flex;
            align-items:center;
            justify-content:space-between;
            padding:0 18px;
            box-shadow:0 4px 10px rgba(0,0,0,0.12);
            z-index:100;
        }
        .logo{
            font-weight:800;
            letter-spacing:2px;
            font-size:20px;
            text-transform:none;
            /* changed to black as requested */
            color:#000;
        }
        .top-actions{
            display:flex;
            gap:10px;
            align-items:center;
        }
        .btn{
            text-decoration:none;
            padding:8px 14px;
            border-radius:8px;
            font-weight:700;
            border:2px solid rgba(0,0,0,0.06);
            transition:all .18s ease;
            box-shadow:0 2px 6px rgba(0,0,0,0.08);
            display:inline-block;
        }

        .container{
            max-width:900px;
            margin:100px auto 60px;
            background:var(--card-white);
            color: #471396;
            border-radius:12px;
            padding:22px 28px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.18);
        }
        .page-title{
            margin:0 0 12px 0;
            font-size:20px;
            font-weight:800;
            color: #471396;
            display:flex;
            align-items:center;
            justify-content:flex-start;
        }
        .message{
            background:var(--muted);
            padding:10px 12px;
            border-radius:8px;
            color:var(--pulse-purple);
            margin-bottom:12px;
            font-weight:700;
        }
        form label{
            font-weight:700;
            margin-bottom:6px;
            display:inline-block;
            color:#2b1b3a;
        }
        input[type="file"]{
            padding:8px;
            border-radius:6px;
            border:1px solid #e6d6ff;
            background:transparent;
            color:var(--pulse-purple);
        }
        .submit-btn{
            margin-top:14px;
            padding:10px 16px;
            border-radius:8px;
            font-weight:800;
            cursor:pointer;
            border:none;
            background:var(--pulse-purple);
            color:#fff;
            box-shadow:0 6px 18px rgba(91,43,138,0.28);
        }
        .submit-btn:hover{
            transform:translateY(-2px);
            opacity:0.95;
        }

        @media (max-width:640px){
            .top-bar{padding:0 10px;}
            .container{margin:90px 12px;}
            .logo{font-size:16px;}
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <!-- Left: black text as requested -->
        <div class="logo">upload employee details through excel</div>

        <div class="top-actions">
            <!-- Back: inline CSS, starts white -> hover green -->
            <a href="add_employee_details.php"
               class="btn back-btn"
               style="background:#fff;color:#5b2b8a;border:2px solid rgba(0,0,0,0.08);padding:8px 14px;border-radius:8px;font-weight:700;text-decoration:none;transition:all .18s ease;box-shadow:0 2px 6px rgba(0,0,0,0.06);"
               onmouseover="this.style.background='#4caf50'; this.style.color='#fff'; this.style.borderColor='#4caf50'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.background='#fff'; this.style.color='#5b2b8a'; this.style.borderColor='rgba(0,0,0,0.08)'; this.style.transform='none';"
            >Back</a>

            <!-- Logout: inline CSS, starts white -> hover red -->
            <a href="logout.php"
               class="btn logout-btn"
               style="background:#fff;color:#5b2b8a;border:2px solid rgba(0,0,0,0.08);padding:8px 14px;border-radius:8px;font-weight:700;text-decoration:none;transition:all .18s ease;box-shadow:0 2px 6px rgba(0,0,0,0.06);"
               onmouseover="this.style.background='#ff4d4d'; this.style.color='#fff'; this.style.borderColor='#ff4d4d'; this.style.transform='translateY(-2px)';"
               onmouseout="this.style.background='#fff'; this.style.color='#5b2b8a'; this.style.borderColor='rgba(0,0,0,0.08)'; this.style.transform='none';"
            >Logout</a>
        </div>
    </div>

    <div class="container">
        <h2 class="page-title">Upload Excel to Add Employee Details</h2>
        <?php if ($message): ?>
            <div class="message"><strong><?= $message ?></strong></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <label>Select Excel File:</label><br>
            <input type="file" name="excel_file" accept=".xlsx, .xls" required>
            <br><br>
            <button type="submit" name="upload" class="submit-btn">Upload</button>
        </form>
    </div>
</body>
</html>
