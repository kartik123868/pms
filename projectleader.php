<?php
session_start();

$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'logindetails_db';



$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) {
    die("An error occured" . $conn->connect_error);
}

if(!isset($_SESSION['username'])){
    echo "<script>alert('u need to login');</script>";
    header("Location: home.php");
    exit;
}

$username = $_SESSION['username'];

$sql = "SELECT * FROM lgntable WHERE username='$username' AND usertype='projectleader'";
$res = $conn->query($sql);
if(!$res || $res->num_rows < 1){
    die("you are not project leader!");
}



if(isset($_POST['add'])){
    $pid = $_POST['project_id'];
    $pname = $_POST['project_name'];
    $prop = $_POST['proposal'];
    $sanct = $_POST['sanction'];
    $compl = $_POST['completed'];
    $obj = $_POST['objective'];
    $cost = $_POST['project_cost'];

    if(strlen($pname) < 2){
        echo "small name";
    }

    $ins = "INSERT INTO project(project_leader,project_id,project_name,project_proposal,project_sanction,project_completed,project_objective,project_cost)
            VALUES('$username','$pid','$pname','$prop','$sanct','$compl','$obj','$cost')";
    $conn->query($ins);
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if(isset($_POST['update'])){
    $id = $_POST['id'];
    $pid = $_POST['project_id'];
    $pname = $_POST['project_name'];
    $prop = $_POST['proposal'];
    $sanct = $_POST['sanction'];
    $compl = $_POST['completed'];
    $obj = $_POST['objective'];
    $cost = $_POST['project_cost'];

    if(!empty($pid) && !empty($pname)){
        $upd = "UPDATE project SET project_id='$pid', project_name='$pname', project_proposal='$prop',
        project_sanction='$sanct', project_completed='$compl', project_objective='$obj', project_cost='$cost'
        WHERE project_number=$id AND project_leader='$username'";
        $conn->query($upd);
    } else {
        echo "some values are missing ";
    }
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

if(isset($_GET['delete'])){
    $del = intval($_GET['delete']);
    $conn->query("DELETE FROM project WHERE project_number=$del AND project_leader='$username'");
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

$editing = false;
$editData = [];

if(isset($_GET['edit'])){
    $editing = true;
    $eid = intval($_GET['edit']);
    $res2 = $conn->query("SELECT * FROM project WHERE project_number=$eid AND project_leader='$username'");
    if($res2 && $res2->num_rows > 0){
        $editData = $res2->fetch_assoc();
    }
}


?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<link rel="stylesheet" href="projectleader.css">
<style>
.action-link{padding:5px 10px;background:#fcc00;color:black;text-decoration:none;border-radius:4px;margin:3px;display:inline-block;transition:.2s;}
.action-link:hover{background:green;color:black;transform:scale(1.08);}
input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none;}
</style>
</head>
<body>

<div class="top-bar">
  <div style="font-size: 20px;"> Project Leader Dashboard</div>
  <div>
    <a href="viewreport.php" class="action-link" style="background:#fff;">View Report</a>
    <a href="logout.php" class="action-link" style="background:red;color:#fff;">Logout</a>
  </div>
</div>

<h2 style="text-align:center;"><?php echo($editing ? "Project Edit Mode 🔧" : "NEW PROJECT"); ?></h2>

<form method="POST">
<?php if($editing): ?>
  <input type="hidden" name="id" value="<?php echo $editData['project_number']; ?>">
<?php endif; ?>
Project ID: <input type="number" name="project_id" value="<?php echo($editing ? $editData['project_id'] : ""); ?>" required><br><br>
Project Name: <input type="text" name="project_name" value="<?php echo($editing ? $editData['project_name'] : ""); ?>" required><br><br>
Leader: <input type="text" value="<?php echo $username; ?>" disabled><br><br>
Proposal: <input type="date" name="proposal" value="<?php echo($editing ? $editData['project_proposal'] : ""); ?>" required><br><br>
Sanction: <input type="date" name="sanction" value="<?php echo($editing ? $editData['project_sanction'] : ""); ?>" required><br><br>
Completed: <input type="date" name="completed" value="<?php echo($editing ? $editData['project_completed'] : ""); ?>"><br><br>
Objective: <input type="text" name="objective" value="<?php echo($editing ? $editData['project_objective'] : ""); ?>" required><br><br>
Cost: <input type="number" name="project_cost" step="0.01" value="<?php echo($editing ? $editData['project_cost'] : ""); ?>" required><br><br>
<input type="submit" name="<?php echo($editing ? 'update' : 'add'); ?>" value="<?php echo($editing ? 'Update' : 'Add'); ?> Project">
</form>

<hr>

<h2 style="text-align:center;">CURRENT PROJECT</h2>
<table border="1" cellpadding="8" style="width:100%;border-collapse:collapse;">
<tr>
<th>No.</th><th>ID</th><th>Name</th><th>Leader</th><th>Proposal</th><th>Sanction</th><th>Completed</th><th>Objective</th><th>Cost</th><th>Actions</th>
</tr>
<?php
$r = $conn->query("SELECT * FROM project WHERE project_leader='$username'");
while($pr = $r->fetch_assoc()):
?>
<tr>
<td><?php echo $pr['project_number']; ?></td>
<td><?php echo $pr['project_id']; ?></td>
<td><?php echo $pr['project_name']; ?></td>
<td><?php echo $pr['project_leader']; ?></td>
<td><?php echo $pr['project_proposal']; ?></td>
<td><?php echo $pr['project_sanction']; ?></td>
<td><?php echo $pr['project_completed']; ?></td>
<td><?php echo $pr['project_objective']; ?></td>
<td><?php echo $pr['project_cost']; ?></td>
<td>
  <a href="?edit=<?php echo $pr['project_number']; ?>" class="action-link">Edit</a>
  <a href="?delete=<?php echo $pr['project_number']; ?>" onclick="return confirm('ARE YOU SURE')" class="action-link">Delete</a>
  <a href="project_details.php?project_id=<?php echo $pr['project_number']; ?>" class="action-link">View</a>
</td>
</tr>
<?php endwhile; ?>
</table>

</body>
</html>
