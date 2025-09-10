<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "logindetails_db");
if ($conn->connect_error) {
    die("DB Fail: " . $conn->connect_error);
}

$allEmps = $conn->query("SELECT id,username FROM lgntable WHERE usertype='employee'");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // sanitize / normalize inputs
    $id      = isset($_POST['employee_id']) ? (int)$_POST['employee_id'] : 0;
    $role    = trim($_POST['role'] ?? "noRole");
    $desig   = trim($_POST['designation'] ?? "noDesig");
    $join    = trim($_POST['joining_date'] ?? "1900-01-01");
    $retire  = trim($_POST['retiring_date'] ?? "2100-12-31");
    $addr    = trim($_POST['address'] ?? "Kahin");
    $contact = trim($_POST['contact_number'] ?? "000");
    $aadhaar = trim($_POST['aadhaar_number'] ?? "0000");
    $edu     = trim($_POST['education'] ?? "NA");
    $pay     = isset($_POST['pay_level']) ? (int)$_POST['pay_level'] : 0;
    $promo   = trim($_POST['promotion'] ?? "noPromo");
    $transfer= trim($_POST['transfer'] ?? "noTrans");
    $group   = trim($_POST['emp_group'] ?? "noGroup");

    $ins = "INSERT INTO employee_details (
        employee_id, role, designation, joining_date, retiring_date,
        address, contact_number, aadhaar_number, education, pay_level,
        promotion, transfer, emp_group
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $stmt = $conn->prepare($ins);
    if (!$stmt) {
        $msg = "Internal error: DB prepare failed.";
        echo '<script>(function(){function showAlert(msg,bg,timeout){function append(){var d=document.createElement("div");d.textContent=msg;d.style.cssText="position:fixed;top:20px;left:50%;transform:translateX(-50%);background:"+bg+";color:#fff;padding:12px 18px;border-radius:8px;font-weight:700;box-shadow:0 8px 22px rgba(0,0,0,0.15);z-index:99999;cursor:pointer;min-width:200px;text-align:center;";d.onclick=function(){this.parentNode&&this.parentNode.removeChild(this)};document.body.appendChild(d);setTimeout(function(){d.parentNode&&d.parentNode.removeChild(d)},timeout||5000);} if(document.body){append();}else{document.addEventListener("DOMContentLoaded",append);} } showAlert(' . json_encode($msg) . ',"#e74c3c",5000);})();</script>';
    } else {
        $stmt->bind_param(
            "issssssssisss",
            $id, $role, $desig, $join, $retire,
            $addr, $contact, $aadhaar, $edu, $pay,
            $promo, $transfer, $group
        );

        if ($stmt->execute()) {
            $msg = "Added successfully";
            // show success alert centered at top
            echo '<script>(function(){function showAlert(msg,bg,timeout){function append(){var d=document.createElement("div");d.textContent=msg;d.style.cssText="position:fixed;top:16px;left:50%;transform:translateX(-50%);background:"+bg+";color:#fff;padding:12px 18px;border-radius:8px;font-weight:700;box-shadow:0 8px 22px rgba(0,0,0,0.15);z-index:99999;cursor:pointer;min-width:220px;text-align:center;";d.onclick=function(){this.parentNode&&this.parentNode.removeChild(this)};document.body.appendChild(d);setTimeout(function(){d.parentNode&&d.parentNode.removeChild(d)},timeout||4000);} if(document.body){append();}else{document.addEventListener("DOMContentLoaded",append);} } showAlert("Added successfully","#2ecc71",4000);})();</script>';
        } else {
            $msg = "Error adding record";
            // keep error at top-right
            echo '<script>(function(){function showAlert(msg,bg,timeout){function append(){var d=document.createElement("div");d.textContent=msg;d.style.cssText="position:fixed;top:20px;right:20px;background:"+bg+";color:#fff;padding:12px 18px;border-radius:8px;font-weight:700;box-shadow:0 8px 22px rgba(0,0,0,0.15);z-index:99999;cursor:pointer;min-width:200px;text-align:center;";d.onclick=function(){this.parentNode&&this.parentNode.removeChild(this)};document.body.appendChild(d);setTimeout(function(){d.parentNode&&d.parentNode.removeChild(d)},timeout||5000);} if(document.body){append();}else{document.addEventListener("DOMContentLoaded",append);} } showAlert("Error adding record","#e74c3c",5000);})();</script>';
            // error_log($stmt->error);
        }
        // close statement and nullify to avoid double-close later
        $stmt->close();
        $stmt = null;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Employee</title>
<link rel="stylesheet" href="add_employee_details.css">
</head>
<body>

<div class="top-bar">
  <div class="title">Add Employee Details</div>
  <div class="nav-buttons">
    <a href="seeempdetails.php" class="button">see employee details</a>
    <a href="admin.php" class="button">back</a>
    <a href="logout.php" style="text-decoration:none;color:black;background:white;padding:8px 16px;border-radius:5px;transition:0.3s;" onmouseover="this.style.backgroundColor='red';this.style.color='white';this.style.transform='scale(1.1)';" onmouseout="this.style.backgroundColor='white';this.style.color='black';this.style.transform='scale(1)';">Logout 🚪</a>
    <a href="upload_employee_excel.php" class="button">📊 Excel</a>
    <a href="addemp.php" class="button">add employee</a>
  </div>
</div>

<form method="post" class="employee-form">
  <div class="form-row">
    <label>Select employee :</label>
    <select name="employee_id" required>
      <option value="">-- Select --</option>
      <?php while ($r = $allEmps->fetch_assoc()): ?>
        <option value="<?= htmlspecialchars($r['id']) ?>"><?= htmlspecialchars($r['username']) ?> (ID: <?= htmlspecialchars($r['id']) ?>)</option>
      <?php endwhile; ?>
    </select>

    <label>Role</label>
    <input type="text" name="role" required>
  </div>

  <div class="form-row">
    <label>Designation</label>
    <input type="text" name="designation" required>

    <label>Joining date</label>
    <input type="date" name="joining_date" required>
  </div>

  <div class="form-row">
    <label>Retiring Date </label>
    <input type="date" name="retiring_date" required>

    <label>Address </label>
    <textarea name="address" rows="2" required></textarea>
  </div>

  <div class="form-row">
    <label>Contact Number:</label>
    <input type="text" name="contact_number" required>

    <label>Aadhaar Number </label>
    <input type="text" name="aadhaar_number" required>
  </div>

  <div class="form-row">
    <label>Education </label>
    <input type="text" name="education">

    <label>Salary</label>
    <select name="pay_level" required>
      <option value="">-- Select --</option>
      <option value="12000">12000</option>
      <option value="18000">18000</option>
      <option value="34000">34000</option>
      <option value="60000">60000</option>
    </select>
  </div>

  <div class="form-row">
    <label>Promotion </label>
    <textarea name="promotion" rows="2"></textarea>

    <label>Transfer </label>
    <textarea name="transfer" rows="2"></textarea>
  </div>

  <div class="form-row">
    <label>Group name </label>
    <input type="text" name="emp_group">
  </div>

  <div class="submit-button">
    <input type="submit" value="Submit ">
  </div>
</form>

</body>
</html>

<?php
// only close stmt if it's still set and not null
if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    @ $stmt->close();
    $stmt = null;
}
if ($allEmps) $allEmps->free();
$conn->close();
?>
