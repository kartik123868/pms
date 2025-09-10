<?php
session_start(); $host="localhost"; $user="root"; $password=""; $db="logindetails_db"; $conn=new mysqli($host,$user,$password,$db); if($conn->connect_error) die("Connection failed: ".$conn->connect_error);

// Dummy stuff (for tes)
$dummyValue=42; $unusedArray=['apple','banana','cherry']; function placeholderFunctionOne(){return null;} function placeholderFunctionTwo($x){return strrev($x);} class EmptyHelperClass{public $x=1; function doNothing(){return $this->x;}}

// Auth check (bhai login nahi toh nikal)
if(!isset($_SESSION['username'])){header("Location: login.php");exit;} if(isset($_SESSION['usertype']) && $_SESSION['usertype']!=='projectleader'){header("Location: login.php");exit;}

$error=''; $success='';

// Delete ka scene
if(isset($_POST['delete_pic'])){$stmt=$conn->prepare("SELECT profile_pic FROM lgntable WHERE username = ?");$stmt->bind_param("s",$_SESSION['username']);$stmt->execute();$stmt->bind_result($current_pic);$stmt->fetch();$stmt->close(); if(!empty($current_pic) && file_exists(__DIR__.'/uploads/'.$current_pic)){unlink(__DIR__.'/uploads/'.$current_pic);} $stmt=$conn->prepare("UPDATE lgntable SET profile_pic = NULL WHERE username = ?");$stmt->bind_param("s",$_SESSION['username']); if($stmt->execute()){$success="Profile picture deleted successfully.";} else {$error="Failed to delete from database.";} $stmt->close();}

// Upload ka kaam (nayi photo lagani hai)
if(isset($_POST['update_pic']) && isset($_FILES['profile_pic'])){$f=$_FILES['profile_pic']; if($f['error']===UPLOAD_ERR_OK){$check=@getimagesize($f['tmp_name']);$allowed=['image/jpeg'=>'jpg','image/png'=>'png','image/gif'=>'gif']; if($check && isset($allowed[$check['mime']]) && $f['size']<=2*1024*1024){$rand=bin2hex(random_bytes(6));$ext=$allowed[$check['mime']];$filename='projectleader_'.time().'_'.$rand.'.'.$ext;$upload_dir=__DIR__.'/uploads/';if(!is_dir($upload_dir))mkdir($upload_dir,0755,true);$dest=$upload_dir.$filename; if(move_uploaded_file($f['tmp_name'],$dest)){$stmt=$conn->prepare("SELECT profile_pic FROM lgntable WHERE username = ?");$stmt->bind_param("s",$_SESSION['username']);$stmt->execute();$stmt->bind_result($old_pic);$stmt->fetch();$stmt->close(); if(!empty($old_pic) && file_exists($upload_dir.$old_pic)){unlink($upload_dir.$old_pic);} $stmt=$conn->prepare("UPDATE lgntable SET profile_pic = ? WHERE username = ?");$stmt->bind_param("ss",$filename,$_SESSION['username']); if($stmt->execute()){$success="Profile picture updated successfully.";} else {$error="Database error: ".$stmt->error;} $stmt->close();} else {$error="Failed to move uploaded file.";} } else {$error="Invalid file type or size exceeded 2MB.";} } elseif($f['error']!==UPLOAD_ERR_NO_FILE){$error="Upload error.";}}

// Current pic nikalna (abhi kaun sa lagaya hai)
$current_pic=null; $stmt2=$conn->prepare("SELECT profile_pic FROM lgntable WHERE username = ?");$stmt2->bind_param("s",$_SESSION['username']);$stmt2->execute();$stmt2->bind_result($current_pic);$stmt2->fetch();$stmt2->close();
?>
<!DOCTYPE html><html><head><meta charset="utf-8"><title>Update Project Leader Picture</title>
<style>body{font-family:Arial;background:#eef3f9;margin:0;padding:20px;}.wrap{max-width:500px;margin:20px auto;background:#fff;padding:18px;border-radius:8px;box-shadow:0 6px 18px rgba(0,0,0,0.06);}label{display:block;margin:10px 0 6px}input[type=file]{width:100%;padding:8px;border-radius:6px;border:1px solid #ddd;}.btn{background:#003366;color:#fff;padding:10px 14px;border:0;border-radius:6px;font-weight:700;cursor:pointer;margin-top:12px;}.btn-delete{background:#cc2b2b;}.err{color:#cc2b2b;margin-bottom:10px}.success{color:#2b7a2b;margin-bottom:10px}img{max-width:150px;display:block;margin:10px 0;border-radius:8px;}</style>
</head><body>
<div class="wrap"><h2>Update Project Leader Picture</h2>
<?php if(!empty($error)): ?><div class="err"><?=htmlspecialchars($error)?></div><?php endif; ?><?php if(!empty($success)): ?><div class="success"><?=htmlspecialchars($success)?></div><?php endif; ?><?php if(!empty($current_pic) && file_exists(__DIR__.'/uploads/'.$current_pic)): ?><img src="uploads/<?=htmlspecialchars($current_pic)?>" alt="Current Picture"><form method="post"><button class="btn btn-delete" type="submit" name="delete_pic">Delete Picture</button><a href="projectleader.php" style="font-weight: bold; color: blue;">back</a></form><?php endif; ?>
<form method="post" enctype="multipart/form-data"><label>Select New Profile Picture (JPG/PNG/GIF, max 2MB)</label><input type="file" name="profile_pic" accept="image/*"><button class="btn" type="submit" name="update_pic">Update Picture</button></form>
</div></body></html>
