<?php

$host="localhost";      $user="root";      $password="";      $db="logindetails_db";    session_start();    

$data=mysqli_connect($host,   $user,    $password,   $db);    if($data===false){    die("Connection error");    }     $error="";   

if($_SERVER["REQUEST_METHOD"]=="POST"){    $username=$_POST["username"];     $password=$_POST["password"];    
    $sql="SELECT * FROM lgntable WHERE username='$username' AND password='$password'";    $result=mysqli_query($data,$sql);    
    if($result && mysqli_num_rows($result)>0){    $row=mysqli_fetch_array($result);     $_SESSION["username"]=$username;    
        switch($row["usertype"]){    case "employee":header("Location: employee.php");exit;    case "admin":header("Location: admin.php");exit;    
            case "projectleader":header("Location: projectleader.php");exit;    case "director":header("Location: director.php");exit;    
            case "subdirector":header("Location: subdirector.php");exit;     default:$error="User type not recognized.";  
        }
    }else{    $error="Username or password is incorrect.";    }   
}
?>

<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8">    <title>Login Form</title>   <link rel="stylesheet" href="styleee.css"></head><body>
<div class="header">   <span class="brand">PULSEWORK</span>   </div> 

<div class="login-box"><h2>User Login</h2>   <?php if(!empty($error)):?><p style="color:red;text-align:center;"><?php echo $error;?></p><?php endif;?> 
<form action="#" method="POST"><label>User ID:</label>   <input type="text" name="username" required>     <label for="password">Password:</label>   <input type="password" id="password" name="password" required>   <button type="submit">Login</button>  </form></div>
</body></html>
