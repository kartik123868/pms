<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$user = "root";
$password = "";
$db = "logindetails_db";

$conn = new mysqli($host, $user, $password, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$employees = $conn->query("SELECT id, username FROM lgntable WHERE usertype = 'employee'");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $employee_id = $_POST['employee_id'];
    $role = $_POST['role'];
    $designation = $_POST['designation'];
    $joining_date = $_POST['joining_date'];
    $retiring_date = $_POST['retiring_date'];
    $address = $_POST['address'];
    $contact_number = $_POST['contact_number'];
    $aadhaar_number = $_POST['aadhaar_number'];
    $education = $_POST['education'];
    $pay_level = $_POST['pay_level'];
    $promotion = $_POST['promotion'];
    $transfer = $_POST['transfer'];
    $emp_group = $_POST['emp_group'];

    $stmt = $conn->prepare("INSERT INTO employee_details 
        (employee_id, role, designation, joining_date, retiring_date, address, contact_number, aadhaar_number, education, pay_level, promotion, transfer, emp_group) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("issssssssssss", $employee_id, $role, $designation, $joining_date, $retiring_date, $address, $contact_number, $aadhaar_number, $education, $pay_level, $promotion, $transfer, $emp_group);

    if ($stmt->execute()) {
        echo "<p style='color:green; text-align:center;'>Employee details added successfully!</p>";
    } else {
        echo "<p style='color:red; text-align:center;'>Error: " . $stmt->error . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Employee Details</title>
    <link rel="stylesheet" href="add_employee_details.css">
</head>
<body>

<!-- Top Bar -->
<div class="top-bar">
    <div class="title">Add Employee Details</div>
    <div class="nav-buttons">
        <a href="admin.php" class="button">Back</a>
        <a href="logout.php" 
   style="text-decoration: none; 
          color: black; 
          background-color: white; 
          padding: 8px 16px; 
          border-radius: 5px; 
          transition: all 0.3s ease;" 
   onmouseover="this.style.backgroundColor='red'; this.style.color='white'; this.style.transform='scale(1.1)';"
   onmouseout="this.style.backgroundColor='white'; this.style.color='black'; this.style.transform='scale(1)';">
   Logout
</a>

        <a href="upload_employee_excel.php" class="button">Excel</a>
        <a href="addemp.php" class="button">Add New Employee</a>
    </div>
</div>

<!-- Employee Form -->
<form method="post" class="employee-form">
    <div class="form-row">
        <label>Select Employee:</label>
        <select name="employee_id" required>
            <option value="">-- Select --</option>
            <?php while ($emp = $employees->fetch_assoc()): ?>
                <option value="<?= $emp['id'] ?>"><?= $emp['username'] ?> (ID: <?= $emp['id'] ?>)</option>
            <?php endwhile; ?>
        </select>

        <label>Role:</label>
        <input type="text" name="role" required>
    </div>

    <div class="form-row">
        <label>Designation:</label>
        <input type="text" name="designation" required>

        <label>Joining Date:</label>
        <input type="date" name="joining_date" required>
    </div>

    <div class="form-row">
        <label>Retiring Date:</label>
        <input type="date" name="retiring_date" required>

        <label>Address:</label>
        <textarea name="address" rows="2" required></textarea>
    </div>

    <div class="form-row">
        <label>Contact Number:</label>
        <input type="text" name="contact_number" required>

        <label>Aadhaar Number:</label>
        <input type="text" name="aadhaar_number" required>
    </div>

    <div class="form-row">
        <label>Education:</label>
        <input type="text" name="education">

        <label>Pay Level:</label>
        <select name="pay_level" required>
            <option value="">-- Select Pay Level --</option>
            <option value="12000">12000</option>
            <option value="18000">18000</option>
            <option value="34000">34000</option>
            <option value="60000">60000</option>
        </select>
    </div>

    <div class="form-row">
        <label>Promotion:</label>
        <textarea name="promotion" rows="2"></textarea>

        <label>Transfer:</label>
        <textarea name="transfer" rows="2"></textarea>
    </div>

    <div class="form-row">
        <label>Group:</label>
        <input type="text" name="emp_group">
    </div>

    <div class="submit-button">
        <input type="submit" value="Add Employee Details">
    </div>
</form>

</body>
</html>
