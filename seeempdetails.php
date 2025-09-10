<?php
session_start();
if (!isset($_SESSION['username'])) {
    header('Location: login.php');
    exit();
}

$conn = new mysqli("localhost", "root", "", "logindetails_db");
if ($conn->connect_error) {
    die("DB connection failed: " . $conn->connect_error);
}

// fetch employees for the "select -> edit" dropdown
$employees = $conn->query("SELECT id, username FROM lgntable WHERE usertype='employee' ORDER BY username ASC");

// --- Handle delete action (POST) using PRG pattern ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $did = (int) $_POST['delete_id'];
    $delStmt = $conn->prepare("DELETE FROM employee_details WHERE employee_id = ? LIMIT 1");
    if ($delStmt) {
        $delStmt->bind_param("i", $did);
        if ($delStmt->execute()) {
            $_SESSION['flash'] = ['type' => 'success', 'msg' => "Deleted employee ID {$did}"];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Error deleting record'];
        }
        $delStmt->close();
    } else {
        $_SESSION['flash'] = ['type' => 'error', 'msg' => 'Internal DB error'];
    }

    // Redirect to same page (preserve query string if present) to avoid POST state
    $qs = $_SERVER['QUERY_STRING'] ?? '';
    $loc = $_SERVER['PHP_SELF'] . ($qs ? ('?' . $qs) : '');
    header("Location: " . $loc);
    exit;
}

// Search query (by username or employee_id)
$search = trim($_GET['q'] ?? '');
$export = isset($_GET['export']) && $_GET['export'] === 'csv';

$baseSql = "SELECT e.*, l.username FROM employee_details e LEFT JOIN lgntable l ON e.employee_id = l.id";
$stmt = null;
$res = null;

if ($search !== '') {
    $sql = $baseSql . " WHERE l.username LIKE ? OR CAST(e.employee_id AS CHAR) LIKE ? ORDER BY e.employee_id ASC";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $like = "%{$search}%";
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $res = $stmt->get_result();
        $stmt->close();
        $stmt = null;
    } else {
        $res = $conn->query("SELECT e.*, l.username FROM employee_details e LEFT JOIN lgntable l ON e.employee_id = l.id WHERE 0");
    }
} else {
    $sql = $baseSql . " ORDER BY e.employee_id ASC";
    $res = $conn->query($sql);
}

// CSV export
if ($export) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="employees.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['employee_id','username','role','designation','joining_date','retiring_date','address','contact_number','aadhaar_number','education','pay_level','promotion','transfer','emp_group']);
    while ($row = $res->fetch_assoc()) {
        $aad = $row['aadhaar_number'] ?? '';
        $masked = preg_replace('/.(?=.{4})/', '*', $aad);
        fputcsv($out, [
            $row['employee_id'] ?? '',
            $row['username'] ?? '',
            $row['role'] ?? '',
            $row['designation'] ?? '',
            $row['joining_date'] ?? '',
            $row['retiring_date'] ?? '',
            $row['address'] ?? '',
            $row['contact_number'] ?? '',
            $masked,
            $row['education'] ?? '',
            $row['pay_level'] ?? '',
            $row['promotion'] ?? '',
            $row['transfer'] ?? '',
            $row['emp_group'] ?? ''
        ]);
    }
    fclose($out);
    if ($res) $res->free();
    if ($employees) $employees->free();
    $conn->close();
    exit;
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>All Employees</title>
<link rel="stylesheet" href="add_employee_details.css">
<style>
:root{--purple:#3e0e72;--yellow:#ffd400;}
html,body{height:100%;margin:0;padding:0;font-family:Arial,Helvetica,sans-serif;background:var(--purple);color:#fff}
.page-wrapper{max-width:1200px;margin:20px auto;padding:16px}
.top-bar{background:var(--yellow);padding:20px;border-radius:6px;color:#000}
.top-bar .title{font-size:32px;font-weight:700}
.controls{display:flex;flex-wrap:wrap;gap:10px;align-items:center;margin:12px 0}
.search-input{padding:8px;border-radius:6px;border:1px solid #ccc}
.card{background:#fff;color:#111;padding:12px;border-radius:8px;margin-top:10px;box-shadow:0 6px 24px rgba(0,0,0,0.25)}
.table-responsive { overflow-x:auto; -webkit-overflow-scrolling:touch; padding-bottom:8px; position:relative; }

/* improved table layout so actions have room */
table{width:100%; table-layout:fixed; border-collapse:collapse; font-size:13px; }
th,td{padding:6px 8px; border:1px solid #e6e6e6; text-align:left; vertical-align:top; word-break:break-word; overflow-wrap:break-word; white-space:normal; box-sizing:border-box;}
thead th{background:#f4f4f4;color:#222;font-weight:700}
tbody tr{transition:transform .12s ease, background-color .12s ease}
tbody tr:hover{background:#e6f9e8;transform:translateY(-4px)}
.mask{letter-spacing:2px}

/* Column layout (increase Actions width so Edit+Delete fit) */
th:nth-child(1), td:nth-child(1)  { width:6%;  }
th:nth-child(2), td:nth-child(2)  { width:12%; }
th:nth-child(3), td:nth-child(3)  { width:8%;  }
th:nth-child(4), td:nth-child(4)  { width:14%; }
th:nth-child(5), td:nth-child(5)  { width:8%;  }
th:nth-child(6), td:nth-child(6)  { width:8%;  }
th:nth-child(7), td:nth-child(7)  { width:8%;  }
th:nth-child(8), td:nth-child(8)  { width:8%;  }
th:nth-child(9), td:nth-child(9)  { width:10%; }
th:nth-child(10), td:nth-child(10){ width:6%;  }
th:nth-child(11), td:nth-child(11){ width:9%;  }

/* <<< bigger actions column so buttons don't overlap >>> */
th:nth-child(12), td:nth-child(12){ width:150px; min-width:120px; }

/* Actions cell */
td.actions {
  display:flex;
  align-items:center;
  justify-content:flex-end;
  gap:8px;
  padding:8px;
  overflow:hidden;
  box-sizing:border-box;
}

/* Edit + Delete quick buttons */
.actions a.edit-link,
.actions button.delete-btn {
  display:inline-block;
  white-space:nowrap;
  padding:6px 10px;
  border-radius:6px;
  font-weight:700;
  border:1px solid rgba(0,0,0,0.12);
  background:#fff;
  color:#000;
  cursor:pointer;
  transition:transform .12s ease, box-shadow .12s ease, background .12s ease, color .12s ease;
}
.actions a.edit-link:hover,
.actions button.delete-btn:hover { background:#2ecc71; color:#fff; transform:scale(1.03); box-shadow:0 8px 22px rgba(46,204,113,0.14); }

/* top select/edit controls: make select larger and responsive */
.select-edit { padding:8px;border-radius:6px;border:1px solid rgba(0,0,0,0.15);background:#fff;color:#000;font-weight:600; min-width:240px; max-width:60vw; width:360px; }
.select-edit-btn {
  background:#ffffff;color:#000;padding:8px 12px;border-radius:6px;border:1px solid rgba(0,0,0,0.15);font-weight:600;display:inline-block;text-decoration:none;cursor:pointer;
  transition:transform .15s ease, background-color .15s ease, color .15s ease, box-shadow .15s ease;
}
.select-edit-btn:hover{ background:#2ecc71; color:#ffffff; transform:scale(1.05); box-shadow:0 8px 22px rgba(46,204,113,0.18); }

/* make sure on small screens controls stack nicely */
@media (max-width:900px){
  .select-edit{width:100%; max-width:100%;}
  th:nth-child(4), td:nth-child(4) { width:18%; }
  th:nth-child(2), td:nth-child(2) { width:14%; }
  th:nth-child(9), td:nth-child(9) { width:12%; }
  table{font-size:12px}
  th,td{padding:6px}
  td.actions { justify-content:flex-start; }
}
</style>
</head>
<body>

<?php
// Flash display (if any) — runs on the GET after redirect
if (!empty($_SESSION['flash'])):
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    $bg = ($f['type'] === 'success') ? '#2ecc71' : '#e74c3c';
    $msg_js = json_encode($f['msg']);
?>
<script>
(function(){
  function appendAlert(msg,bg,timeout){
    function doAppend(){
      var d=document.createElement('div');
      d.textContent=msg;
      d.style.cssText="position:fixed;top:16px;left:50%;transform:translateX(-50%);background:"+bg+";color:#fff;padding:12px 18px;border-radius:8px;font-weight:700;z-index:99999;min-width:220px;text-align:center;";
      d.onclick=function(){this.parentNode&&this.parentNode.removeChild(this);};
      document.body.appendChild(d);
      setTimeout(function(){d.parentNode&&d.parentNode.removeChild(d);}, timeout||3500);
    }
    if(document.body) doAppend(); else document.addEventListener('DOMContentLoaded', doAppend);
  }
  appendAlert(<?= $msg_js ?>, '<?= $bg ?>', 3500);
})();
</script>
<?php endif; ?>

<div class="page-wrapper">
  <div class="top-bar"><div class="title">All Employees</div></div>

  <div class="controls">
  <a href="add_employee_details.php"
   style="background:#ffffff;color:#000;padding:8px 12px;border-radius:6px;border:1px solid rgba(0,0,0,0.15);font-weight:600;text-decoration:none;display:inline-block;transition:transform .15s ease, background-color .15s ease, color .15s ease, box-shadow .15s ease;box-shadow:0 2px 6px rgba(0,0,0,0.06);"
   onmouseover="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
   onmouseout="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';"
   onfocus="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
   onblur="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';"
   role="button" aria-label="Back to add employee">
  Back
</a>


    <!-- SELECT -> EDIT control (wider now) -->
    <form method="get" action="editempadmin.php" style="display:inline-block;margin-left:6px;">
    
    
    </form>

    <form method="get" style="display:inline-block;margin-left:8px;">
      <input class="search-input" type="text" name="q" placeholder="Search by username or id" value="<?= htmlspecialchars($search) ?>">
   <input type="submit" value="Search"
  style="background:#ffffff;color:#000;padding:8px 12px;border-radius:6px;border:1px solid rgba(0,0,0,0.15);font-weight:600;cursor:pointer;transition:transform .15s ease, background-color .15s ease, color .15s ease, box-shadow .15s ease;box-shadow:0 2px 6px rgba(0,0,0,0.06);"
  onmouseover="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
  onmouseout="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';"
  onfocus="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
  onblur="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';"
/>

    </form>

    <form method="get" style="display:inline-block;margin-left:6px;">
   <input type="hidden" name="q" value="<?= htmlspecialchars($search) ?>">
<input type="hidden" name="export" value="csv">
<button type="submit"
  style="background:#ffffff;color:#000;padding:8px 12px;border-radius:6px;border:1px solid rgba(0,0,0,.15);font-weight:600;cursor:pointer;transition:transform .15s ease, background-color .15s ease, color .15s ease, box-shadow .15s ease;box-shadow:0 2px 6px rgba(0,0,0,0.06);"
  onmouseover="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
  onmouseout="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';"
  onfocus="this.style.background='#2ecc71'; this.style.color='#ffffff'; this.style.transform='scale(1.05)'; this.style.boxShadow='0 8px 22px rgba(46,204,113,0.18)';"
  onblur="this.style.background='#ffffff'; this.style.color='#000000'; this.style.transform='scale(1)'; this.style.boxShadow='0 2px 6px rgba(0,0,0,0.06)';">
  Download
</button>

    </form>
  </div>

  <div class="card">
    <div class="table-responsive">
      <table>
        <thead>
          <tr>
            <th>Employee (ID)</th>
            <th>Username</th>
            <th>Role</th>
            <th>Designation</th>
            <th>Joining</th>
            <th>Retiring</th>
            <th>Contact</th>
            <th>Aadhaar</th>
            <th>Education</th>
            <th>Pay</th>
            <th>Group</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($res && $res->num_rows > 0): while ($row = $res->fetch_assoc()):
            $eid = htmlspecialchars($row['employee_id'] ?? '');
            $username = htmlspecialchars($row['username'] ?? '');
            $role = htmlspecialchars($row['role'] ?? '');
            $desig = htmlspecialchars($row['designation'] ?? '');
            $join = htmlspecialchars($row['joining_date'] ?? '');
            $retire = htmlspecialchars($row['retiring_date'] ?? '');
            $contact = htmlspecialchars($row['contact_number'] ?? '');
            $aad = $row['aadhaar_number'] ?? '';
            $maskedAad = htmlspecialchars(preg_replace('/.(?=.{4})/', '*', $aad));
            $edu = htmlspecialchars($row['education'] ?? '');
            $pay = htmlspecialchars($row['pay_level'] ?? '');
            $group = htmlspecialchars($row['emp_group'] ?? '');
        ?>
          <tr>
            <td><?= $eid ?></td>
            <td><?= $username ?></td>
            <td><?= $role ?></td>
            <td><?= $desig ?></td>
            <td><?= $join ?></td>
            <td><?= $retire ?></td>
            <td><?= $contact ?></td>
            <td class="mask"><?= $maskedAad ?></td>
            <td><?= $edu ?></td>
            <td><?= $pay ?></td>
            <td><?= $group ?></td>

            <td class="actions" aria-label="Actions column">
              <!-- per-row edit link (now fits) -->
              <a class="edit-link" href="editempadmin.php?emp=<?= urlencode($eid) ?>"
                 title="Edit employee <?= htmlspecialchars($eid) ?>">Edit</a>

              <form method="post" style="display:inline-block;margin:0;" onsubmit="return confirm('Delete this record?');">
                <input type="hidden" name="delete_id" value="<?= $eid ?>">
                <button type="submit"
                        class="delete-btn"
                        title="Delete employee <?= htmlspecialchars($eid) ?>"
                        aria-label="Delete employee <?= htmlspecialchars($eid) ?>">Delete</button>
              </form>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr><td colspan="12">No employee records found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>console.log('view_employees: select->edit updated to editempadmin.php and actions fixed');</script>
</body>
</html>

<?php
if (isset($stmt) && $stmt instanceof mysqli_stmt) { @ $stmt->close(); }
if ($res) $res->free();
if ($employees) $employees->free();
$conn->close();
?>
