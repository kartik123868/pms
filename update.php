<?php 
$host = "localhost";
$user = "root";
$pass = "";
$db = "logindetails_db";

$conn = mysqli_connect($host, $user, $pass, $db);

session_start();
if (!isset($_SESSION["username"])) {
  header("location:login.php");
  exit();
}

// Get user ID from URL
if (isset($_GET['id'])) {
  $id = $_GET['id'];
} else {
  echo "No user ID provided.";
  exit();
}

// Fetch user data
$select = "SELECT * FROM lgntable WHERE id='$id'";
$data = mysqli_query($conn, $select);

if (!$data || mysqli_num_rows($data) === 0) {
  echo "User not found.";
  exit();
}

$row = mysqli_fetch_array($data);

// Handle form submission
if (isset($_POST['save_btn'])) {
  $username = $_POST['username'];
  $password = $_POST['password'];
  $usertype = $_POST['usertype'];

  $update = "UPDATE lgntable SET username='$username', password='$password', usertype='$usertype' WHERE id='$id'";
  $result = mysqli_query($conn, $update);

  if ($result) {
    echo "<script>alert('Record updated successfully'); window.location.href='view.php';</script>";
    exit();
  } else {
    echo "<script>alert('Failed to update record');</script>";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Update</title>
  <link rel="stylesheet" href="update.css" />
</head>
<body>

  <div class="header">
    <div class="title">Update Account</div>
    <div class="buttons">
      <a href="view.php" id="back" class="btn">Back</a>
      <a href="home.php" id="logout" class="btn">Logout</a>
    </div>
  </div> 

  <!-- Centered form container -->
  <div class="form-container">
    <form action="" method="post" id="update-form">
      <label for="username" class="form-label">Username</label>
      <input type="text" name="username" id="username" class="form-input" value="<?php echo htmlspecialchars($row['username']); ?>" required>

      <label for="password" class="form-label">Password</label>
      <input type="password" name="password" id="password" class="form-input" value="<?php echo htmlspecialchars($row['password']); ?>" required>

      <label for="usertype" class="form-label">User Type</label>
      <select name="usertype" id="usertype" class="form-select">
        <option value="employee" <?php if($row['usertype'] == 'employee') echo 'selected'; ?>>employee</option>
        <option value="admin" <?php if($row['usertype'] == 'admin') echo 'selected'; ?>>admin</option>
        <option value="projectleader" <?php if($row['usertype'] == 'projectleader') echo 'selected'; ?>>projectleader</option>
        <option value="director" <?php if($row['usertype'] == 'director') echo 'selected'; ?>>director</option>
        <option value="subdirector" <?php if($row['usertype'] == 'subdirector') echo 'selected'; ?>>subdirector</option>
      </select>

      <button type="submit" class="submit-btn" name="save_btn">Submit</button>
    </form>
  </div>

</body>
</html>

