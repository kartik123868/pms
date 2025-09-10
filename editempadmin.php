<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

/* DB connection */
$conn = new mysqli("localhost","root","","logindetails_db");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

/* helper */
function e($v){ return htmlspecialchars($v ?? '', ENT_QUOTES); }

/* flash helper (prints JS alert box at top center) */
function print_flash_js($flash) {
    $msg_js = json_encode($flash['msg']);
    $bg = ($flash['type'] === 'success') ? '#2ecc71' : '#e74c3c';
    echo "<script>(function(){function append(){var d=document.createElement('div');d.textContent={$msg_js};d.style.cssText='position:fixed;top:16px;left:50%;transform:translateX(-50%);background:{$bg};color:#fff;padding:12px 18px;border-radius:8px;font-weight:700;z-index:99999;min-width:220px;text-align:center;';d.onclick=function(){this.parentNode&&this.parentNode.removeChild(this)};document.body.appendChild(d);setTimeout(function(){d.parentNode&&d.parentNode.removeChild(d)},3500);} if(document.body) append(); else document.addEventListener('DOMContentLoaded',append);})();</script>";
}

/* POST handling:
   - If editing an existing details row: hidden input 'detail_id' -> UPDATE that row
   - If creating a new details for an employee: hidden input 'create_for_emp' -> INSERT and redirect to list page
*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // common sanitized inputs
    $role    = trim($_POST['role'] ?? '');
    $designation = trim($_POST['designation'] ?? '');
    $joining_date = trim($_POST['joining_date'] ?? null);
    $retiring_date = trim($_POST['retiring_date'] ?? null);
    $address = trim($_POST['address'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $aadhaar_number = trim($_POST['aadhaar_number'] ?? '');
    $education = trim($_POST['education'] ?? '');
    $pay_level = trim($_POST['pay_level'] ?? '');
    $promotion = trim($_POST['promotion'] ?? '');
    $transfer = trim($_POST['transfer'] ?? '');
    $emp_group = trim($_POST['emp_group'] ?? '');

    if (isset($_POST['detail_id']) && (int)$_POST['detail_id'] > 0) {
        // UPDATE existing details row
        $detail_id = (int)$_POST['detail_id'];
        $sql = "UPDATE employee_details SET
                    role=?, designation=?, joining_date=?, retiring_date=?, address=?, contact_number=?, aadhaar_number=?, education=?, pay_level=?, promotion=?, transfer=?, emp_group=?
                WHERE id=? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'DB prepare failed (update).'];
            header("Location: editempadmin.php?id=".$detail_id);
            exit;
        }
        $types = str_repeat('s', 12) . 'i'; // 12 strings then 1 int (id)
        $bind = $stmt->bind_param(
            $types,
            $role, $designation, $joining_date, $retiring_date, $address,
            $contact_number, $aadhaar_number, $education, $pay_level,
            $promotion, $transfer, $emp_group,
            $detail_id
        );
        if ($bind === false) {
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'error','msg'=>'DB bind failed (update).'];
            header("Location: editempadmin.php?id=".$detail_id);
            exit;
        }
        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Employee details updated.'];
            // <-- on success go back to the listing page
            header("Location: seeempdetails.php");
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'error','msg'=>"Update failed: {$err}"];
            header("Location: editempadmin.php?id=".$detail_id);
            exit;
        }
    } elseif (isset($_POST['create_for_emp']) && (int)$_POST['create_for_emp'] > 0) {
        // INSERT new details for employee_id
        $employee_id = (int)$_POST['create_for_emp'];
        $sql = "INSERT INTO employee_details (
                    employee_id, role, designation, joining_date, retiring_date,
                    address, contact_number, aadhaar_number, education, pay_level,
                    promotion, transfer, emp_group
                ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'DB prepare failed (insert).'];
            header("Location: editempadmin.php?emp=".$employee_id);
            exit;
        }
        // types: i then 12 strings
        $types = 'i' . str_repeat('s', 12);
        $bind = $stmt->bind_param(
            $types,
            $employee_id,
            $role, $designation, $joining_date, $retiring_date,
            $address, $contact_number, $aadhaar_number, $education, $pay_level,
            $promotion, $transfer, $emp_group
        );
        if ($bind === false) {
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'error','msg'=>'DB bind failed (insert).'];
            header("Location: editempadmin.php?emp=".$employee_id);
            exit;
        }
        if ($stmt->execute()) {
            $newId = $conn->insert_id;
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'success','msg'=>'Employee details created.'];
            // <-- on success go back to the listing page
            header("Location: seeempdetails.php");
            exit;
        } else {
            $err = $stmt->error;
            $stmt->close();
            $_SESSION['flash'] = ['type'=>'error','msg'=>"Insert failed: {$err}"];
            header("Location: editempadmin.php?emp=".$employee_id);
            exit;
        }
    } else {
        // invalid POST
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Invalid form submission.'];
        header("Location: editempadmin.php");
        exit;
    }
}

/* GET: load existing record:
   priority: ?id=<employee_details.id>   OR   ?emp=<employee_id> (pick latest details row)
*/
$record = null;
$detailId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$employeeId = isset($_GET['emp']) ? (int)$_GET['emp'] : 0;

if ($detailId > 0) {
    $q = $conn->prepare("SELECT * FROM employee_details WHERE id = ? LIMIT 1");
    if ($q) {
        $q->bind_param("i",$detailId);
        $q->execute();
        $res = $q->get_result();
        $record = $res->fetch_assoc();
        $q->close();
    }
} elseif ($employeeId > 0) {
    $q = $conn->prepare("SELECT * FROM employee_details WHERE employee_id = ? ORDER BY id DESC LIMIT 1");
    if ($q) {
        $q->bind_param("i",$employeeId);
        $q->execute();
        $res = $q->get_result();
        $record = $res->fetch_assoc();
        $q->close();
    }
}

/* If no record but we have ?emp= we will present a 'create' form for that employee.
   If no id and no emp, show friendly message.
*/
if (!$record && $detailId === 0 && $employeeId === 0) {
    // no context provided
    echo "<!doctype html><html><head><meta charset='utf-8'><title>Edit employee</title><style>body{background:#3e0e72;color:#fff;font-family:Arial;padding:40px;text-align:center} a{color:#ffd400}</style></head><body>";
    echo "<h2>No record selected.</h2><p>Please open this page with <code>?id=</code> or <code>?emp=</code>. <a href='seeempdetails.php'>Back to list</a></p></body></html>";
    $conn->close();
    exit;
}

/* show HTML form (either edit or create) */
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $record ? "Edit" : "Create" ?> Employee Details</title>
<link rel="stylesheet" href="add_employee_details.css">
<style>
:root{--purple:#3e0e72;--yellow:#ffd400;}
html,body{height:100%;margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:var(--purple);color:#fff}
.container{max-width:980px;margin:18px auto;padding:12px}
.top-bar{background:var(--yellow);padding:16px;border-radius:6px;color:#000;display:flex;justify-content:space-between;align-items:center}
.title{font-size:20px;font-weight:700}
.form-card{background:#fff;color:#111;padding:14px;border-radius:8px;margin-top:12px;box-shadow:0 8px 24px rgba(0,0,0,0.18)}
.row{display:flex;gap:12px;margin-bottom:10px;flex-wrap:wrap}
.col{flex:1;min-width:180px}
label{display:block;font-size:13px;margin-bottom:6px;color:#333}
input[type="text"], input[type="date"], select, textarea{width:100%;padding:8px;border-radius:6px;border:1px solid #ccc;box-sizing:border-box}
textarea{resize:vertical}
.actions{display:flex;gap:10px;justify-content:flex-end;margin-top:12px}
.btn {
  background:#ffffff;color:#000;padding:8px 12px;border-radius:6px;border:1px solid rgba(0,0,0,0.15);font-weight:600;text-decoration:none;cursor:pointer;
  transition:transform .12s ease, background-color .12s ease, color .12s ease;
}
.btn:hover{ background:#2ecc71; color:#fff; transform:scale(1.04); box-shadow:0 8px 22px rgba(46,204,113,0.12) }
.btn-cancel{ background:#f8f9fa;color:#000 }
small.note{display:block;color:#666;margin-top:6px}
</style>
</head>
<body>

<div class="container">
  <div class="top-bar">
    <div class="title"><?= $record ? "Edit" : "Create" ?> Employee Details</div>
    <div>
      <a href="seeempdetails.php" class="btn" style="margin-right:8px;">Back to list</a>
      <a href="admin.php" class="btn btn-cancel">Admin</a>
    </div>
  </div>

  <div class="form-card">
    <form method="post" autocomplete="off">
      <?php if ($record): ?>
        <input type="hidden" name="detail_id" value="<?= e($record['id']) ?>">
      <?php else: ?>
        <input type="hidden" name="create_for_emp" value="<?= e($employeeId) ?>">
      <?php endif; ?>

      <div class="row">
        <div class="col">
          <label>Employee (linked id)</label>
          <input type="text" readonly value="<?= e($record['employee_id'] ?? $employeeId) ?>">
          <small class="note">Linked user id (from lgntable). Not editable here.</small>
        </div>

        <div class="col">
          <label>Role</label>
          <input type="text" name="role" value="<?= e($record['role'] ?? '') ?>">
        </div>

        <div class="col">
          <label>Designation</label>
          <input type="text" name="designation" value="<?= e($record['designation'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Joining Date</label>
          <input type="date" name="joining_date" value="<?= e($record['joining_date'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Retiring Date</label>
          <input type="date" name="retiring_date" value="<?= e($record['retiring_date'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Pay Level</label>
          <input type="text" name="pay_level" value="<?= e($record['pay_level'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col" style="flex:2">
          <label>Address</label>
          <textarea name="address" rows="2"><?= e($record['address'] ?? '') ?></textarea>
        </div>
        <div class="col">
          <label>Contact Number</label>
          <input type="text" name="contact_number" value="<?= e($record['contact_number'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Aadhaar Number</label>
          <input type="text" name="aadhaar_number" value="<?= e($record['aadhaar_number'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Education</label>
          <input type="text" name="education" value="<?= e($record['education'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Promotion</label>
          <input type="text" name="promotion" value="<?= e($record['promotion'] ?? '') ?>">
        </div>
        <div class="col">
          <label>Transfer</label>
          <input type="text" name="transfer" value="<?= e($record['transfer'] ?? '') ?>">
        </div>
      </div>

      <div class="row">
        <div class="col">
          <label>Group</label>
          <input type="text" name="emp_group" value="<?= e($record['emp_group'] ?? '') ?>">
        </div>
      </div>

      <div class="actions">
        <a href="seeempdetails.php" class="btn btn-cancel">Cancel</a>
        <button  type="submit" class="btn" onclick="this.disabled=true;this.form.submit();"><?= $record ? 'Save changes' : 'Create details' ?></button>
      </div>
    </form>
  </div>
</div>

<?php
// show flash if any
if (!empty($_SESSION['flash'])) {
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    print_flash_js($f);
}

$conn->close();
?>
</body>
</html>
