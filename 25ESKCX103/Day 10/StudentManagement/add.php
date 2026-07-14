<!DOCTYPE html>

<html>

<head>

<title>Add Student</title>

</head>

<body>

<h2>Add Student</h2>

<form action="save.php" method="POST" enctype="multipart/form-data">

Name

<input type="text" name="name">

<br><br>

Email

<input type="email" name="email">

<br><br>

Branch

<select name="branch">

<option>CSE</option>
<option>IT</option>
<option>ECE</option>
<option>ME</option>

</select>

<br><br>

CGPA

<input type="number" step="0.01" name="cgpa">

<br><br>

Status

<select name="status">

<option>Active</option>
<option>Inactive</option>

</select>

<br><br>

Photo

<input type="file" name="photo">

<br><br>

<button type="submit">

Save Student

</button>

</form>

</body>

</html>