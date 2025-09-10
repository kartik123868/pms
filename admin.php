<?php 
session_start();
if(!isset($_SESSION["username"])){
  header("location:home.php");
}
 ?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Page</title>
  <link rel="stylesheet" href="admin.css">
</head>
<body>

  <div class="top-bar">
    <h1>Admin Dashboard</h1>
    <a href="logout.php" class="logout-btn">Logout</a>
  </div>

  <div class="center-links">
    <a href="viewproject.php">See Projects</a>
    <a href="add.php">Create New Account</a>
   
    <a href="view.php">View/Edit Account</a>
    <a href="add_employee_details.php">Add Employee Details</a>
  </div>

</body>
</html>
