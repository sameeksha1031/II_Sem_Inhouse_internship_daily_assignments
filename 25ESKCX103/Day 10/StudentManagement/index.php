<?php
include 'db.php';

$search = $_GET['search'] ?? "";
$branch = $_GET['branch'] ?? "";
$status = $_GET['status'] ?? "Active";

$sql = "SELECT * FROM students WHERE 1";

if($search!=""){
$sql.=" AND (name LIKE '%$search%' OR email LIKE '%$search%')";
}

if($branch!=""){
$sql.=" AND branch='$branch'";
}

if($status!="All"){
$sql.=" AND status='$status'";
}

$result=mysqli_query($conn,$sql);

$total=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) total FROM students"));
$avg=mysqli_fetch_assoc(mysqli_query($conn,"SELECT AVG(cgpa) avg FROM students"));

?>

<!DOCTYPE html>
<html>
<head>

<title>Student Management</title>

<style>

body{
font-family:Arial;
background:#f5f5f5;
padding:20px;
}

table{
width:100%;
border-collapse:collapse;
background:white;
}

th,td{
padding:10px;
border:1px solid #ddd;
text-align:center;
}

img{
width:60px;
height:60px;
border-radius:50%;
}

.card{
display:inline-block;
padding:15px;
margin:10px;
background:#2196f3;
color:white;
border-radius:10px;
}

</style>

</head>

<body>

<h1>Student Management System</h1>

<div class="card">
Total Students
<h2><?php echo $total['total']; ?></h2>
</div>

<div class="card">
Average CGPA
<h2><?php echo round($avg['avg'],2); ?></h2>
</div>

<br><br>

<form>

<input type="text" name="search" placeholder="Search">

<select name="branch">
<option value="">All Branch</option>
<option>CSE</option>
<option>IT</option>
<option>ECE</option>
<option>ME</option>
</select>

<select name="status">
<option>Active</option>
<option>Inactive</option>
<option>All</option>
</select>

<button>Search</button>

<a href="add.php">Add Student</a>

</form>

<br>

<table>

<tr>

<th>Photo</th>
<th>Name</th>
<th>Email</th>
<th>Branch</th>
<th>CGPA</th>
<th>Status</th>

</tr>

<?php

while($row=mysqli_fetch_assoc($result))
{

?>

<tr>

<td>

<img src="uploads/<?php echo $row['photo'];?>">

</td>

<td><?php echo $row['name']; ?></td>

<td><?php echo $row['email']; ?></td>

<td><?php echo $row['branch']; ?></td>

<td><?php echo $row['cgpa']; ?></td>

<td><?php echo $row['status']; ?></td>

</tr>

<?php } ?>

</table>

</body>
</html>