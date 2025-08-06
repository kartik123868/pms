<?php
session_start();
if (!isset($_SESSION["username"])) {
    header("location:login.php");
    exit(); 
}


$conn = mysqli_connect("localhost", "root", "", "logindetails_db");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

if (isset($_POST['submit'])) {
    $username = $_POST['username'];
    $password = $_POST['password']; 
    $usertype = 'employee'; 

    $query = "INSERT INTO lgntable (username, password, usertype) VALUES ('$username', '$password', '$usertype')";
    $result = mysqli_query($conn, $query);

    if ($result) {
        echo "<script>alert('Employee added successfully');</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($conn) . "');</script>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Employee</title>
    <link rel="stylesheet" href="addemp.css"> 
    <style>
        body {
            background-color: #471396;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        .top-bar {
            background-color: #FFCC00; 
            padding: 15px 30px;
            display: flex;
            justify-content: space-between; 
            align-items: center;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .top-bar .title {
            font-size: 20px;
            font-weight: bold;
            color: black;
        }

        .top-bar a {
            text-decoration: none;
            color: black;
            font-weight: bold;
            font-size: 16px;
            padding: 8px 16px;
            background-color: white;
            border-radius: 5px;
            transition: 0.3s;
        }

        .top-bar a:hover {
            background-color: green;
            color: black;
        }

        .container {
            background-color: white;
            padding: 30px;
            margin: 60px auto;
            width: 400px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            text-align: center;
        }

        h2 {
            color: #4b0082;
        }

        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="submit"] {
            background-color: #4b0082;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 15px;
        }

        input[type="submit"]:hover {
            background-color: #350064;
        }
    </style>
</head>
<body>

    <div class="top-bar">
        <div class="title">Add Employee</div>
        <a href="add_employee_details.php">Back</a>
    </div>

    <div class="container">
        <h2>Add New Employee Login</h2>
        <form method="POST" action="">
            <label for="username">Username:</label><br>
            <input type="text" name="username" required><br><br>

            <label for="password">Password:</label><br>
            <input type="password" name="password" required><br><br>

            <input type="submit" name="submit" value="Add Employee">
        </form>
    </div>

</body>
</html>
