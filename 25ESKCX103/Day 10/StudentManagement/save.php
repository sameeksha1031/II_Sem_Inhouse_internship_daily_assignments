<?php

include 'db.php';

$photo=$_FILES['photo']['name'];

move_uploaded_file($_FILES['photo']['tmp_name'],"uploads/".$photo);

$name=$_POST['name'];

$email=$_POST['email'];

$branch=$_POST['branch'];

$cgpa=$_POST['cgpa'];

$status=$_POST['status'];

mysqli_query($conn,"INSERT INTO students(photo,name,email,branch,cgpa,status)

VALUES('$photo','$name','$email','$branch','$cgpa','$status')");

header("Location:index.php");

?>