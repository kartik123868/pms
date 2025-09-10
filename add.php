<?php 
$host = "localhost"; 
$user = "root";
$pass = "";
$db = "logindetails_db";

$conn = mysqli_connect($host, $user, $pass, $db);

session_start();
if (!isset($_SESSION["username"])) {
  header("location:login.php");
}

if(isset($_POST['save_btn'])){
  $uname = $_POST['username'];
  $pname = $_POST['password'];
  $tname = $_POST['usertype'];

  $query = "INSERT INTO lgntable(username, password, usertype) VALUES('$uname', '$pname', '$tname')";
  $data = mysqli_query($conn, $query); 

  if ($data) {
    $success_msg = "Account created successfully!";
  } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Page</title>
  <link rel="stylesheet" href="add.css">
</head>
<body>

  <div class="header">
    <div class="title">Create New Account</div>
    <div class="buttons">
      <a href="admin.php" id="back" class="one">Back</a>
      <a href="home.php" id="logout" class="one">Logout</a>
    </div>
  </div>

  <div class="form-container">

    
    <?php 
      if (isset($success_msg)) {
        echo "<p style='color: green; text-align: center; font-weight: bold;'>$success_msg</p>";
      }
    ?>

    <form action="" method="post">
      <label for="username">Username</label>
      <input type="text" name="username" id="username" required>

      <label for="password">Password</label>
      <input type="password" name="password" id="password" required>

      <label for="usertype">User Type</label>
      <select name="usertype" id="usertype">
        <option value="employee" selected>employee</option>
        <option value="admin">admin</option>
        <option value="projectleader">projectleader</option>
        <option value="director">director</option>
        <option value="subdirector">subdirector</option>
      </select>

      <button type="submit" class="submit-btn" value="save" name="save_btn">Submit</button>
    </form>
  </div>

</body>
</html>
