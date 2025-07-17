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

// Handle deletion if form submitted
if (isset($_POST['delete_btn'])) {
  if (!empty($_POST['user_ids'])) {
    $ids = implode(',', array_map('intval', $_POST['user_ids']));
    $deleteQuery = "DELETE FROM lgntable WHERE id IN ($ids)";
    $result = mysqli_query($conn, $deleteQuery);
    
    if ($result) {
      echo "<script>alert('Selected users deleted successfully'); window.location.href='delete.php';</script>";
      exit();
    } else {
      echo "<script>alert('Failed to delete users');</script>";
    }
  } else {
    echo "<script>alert('No users selected for deletion');</script>";
  }
}

// Fetch all users
$select = "SELECT * FROM lgntable";
$data = mysqli_query($conn, $select);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Delete Users</title>
  <link rel="stylesheet" href="delete.css" />
</head>
<body>

  <div class="header">
    <div class="title">Delete Users</div>
    <div class="buttons">
      <a href="admin.php" id="back" class="btn">Back</a>
      <a href="home.php" id="logout" class="btn">Logout</a>
    </div>
  </div>

  <div class="form-container">
    <form method="post" id="delete-form" onsubmit="return confirm('Are you sure you want to delete selected users?');">
      <table>
        <thead>
          <tr>
            <th>Select</th>
            <th>ID</th>
            <th>Username</th>
            <th>User Type</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = mysqli_fetch_assoc($data)): ?>
            <tr>
              <td><input type="checkbox" name="user_ids[]" value="<?php echo $row['id']; ?>" /></td>
              <td><?php echo htmlspecialchars($row['id']); ?></td>
              <td><?php echo htmlspecialchars($row['username']); ?></td>
              <td><?php echo htmlspecialchars($row['usertype']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
      
      <div class="form-actions">
        
        <button type="submit" class="delete-btn" name="delete_btn">Delete Selected</button>
      </div>
    </form>
  </div>

</body>
</html>
