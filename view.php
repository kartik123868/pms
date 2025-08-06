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
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>View Account</title>
  <link rel="stylesheet" href="view.css">
</head>
<body>

  <div class="header">
    <div class="title">View Account</div>
    <div class="buttons">
      <a href="admin.php" id="back" class="one">Back</a>
      <a href="home.php" id="logout" class="one">Logout</a>
      <a href="delete.php" id="back" class="one">Delete Account</a>
    </div>
  </div>

  <div class="table-wrapper">
    <table class="user-table">
      <tr>
        <th>Username</th>
        <th>Password</th>
        <th>User Type</th>
        <th>Update</th>
      </tr>

      <?php 
      $query = "SELECT * FROM lgntable";
      $data = mysqli_query($conn, $query);
      $result = mysqli_num_rows($data);

      if ($result > 0) {
        while ($row = mysqli_fetch_assoc($data)) {
          echo "<tr>
                  <td>{$row['username']}</td>
                  <td>{$row['password']}</td>
                  <td>{$row['usertype']}</td>
                  <td>
                  <a class='edit' href='update.php?id={$row['id']}'>Edit</a>
                  </td>
                </tr>";
        }
      } else {
        echo "<tr><td colspan='4'>No record found</td></tr>";
      }
      ?>
    </table>
  </div>

</body>
</html>
