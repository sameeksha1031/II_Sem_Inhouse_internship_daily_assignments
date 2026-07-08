<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "registration";


$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$dob = $_POST['dob'];
$branch = $_POST['branch'];


$errors = [];
$success = "";

 
  echo "<br> " . $branch . "<br>";
  // Name: required only
  if ($name === '') {
    $errors['name'] = 'Name is required.';
    echo "Name is required.";
  }

  // Email: required only
  if ($email === '') {
    $errors['email'] = 'Email is required.';
    echo "Email is required.";
  }

  if (empty($errors)) {
    $success = 'Form submitted successfully.';
    
    // Further processing (save to DB, send email, etc.) goes here
  }

  $folderPath = "uploads/";
  if(!is_dir($folderPath)) {
    mkdir($folderPath, 0777, true);
  }

  if (isset($_FILES['myfile'])) {
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

    $extension = strtolower(pathinfo($_FILES['myfile']['name'], PATHINFO_EXTENSION));
    $maxSize = 20 * 1024 * 1024; // 2MB
  }

  if(!in_array($extension, $allowedTypes)) {
    echo "Error: Only JPG, PNG, GIF, and PDF files are allowed.";
  } elseif ($_FILES['myfile']['size'] > $maxSize) {
    echo "Error: File size exceeds the 2MB limit.";
  } else {
    $newFileName = uniqid() . '.' . $extension;
    $destination = $folderPath . $newFileName;

    if (move_uploaded_file($_FILES['myfile']['tmp_name'], $destination)) {
      echo "File uploaded successfully: " . $newFileName;
    } else {
      echo "Error uploading file.";
    }
  }