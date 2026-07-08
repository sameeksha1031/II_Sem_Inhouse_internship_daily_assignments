<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details</title>

    <style>
        body{
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .card{
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            width: 350px;
            text-align: center;
        }

        h1{
            color: #0077cc;
        }

        p{
            font-size: 18px;
            margin: 10px 0;
        }
    </style>
</head>
<body>

<?php
    $name = "Sameeksha";
    $cgpa = 8.9;
    $branch = "Computer Science";
    $currentTime = time();
    $formattedTime = date("d-m-Y h:i:s A", $currentTime);
?>

<div class="card">
    <h1><?= $name ?></h1>
    <p><strong>CGPA:</strong> <?= $cgpa ?></p>
    <p><strong>Branch:</strong> <?= $branch ?></p>
    <p><strong>Current Time:</strong><br><?= $formattedTime ?></p>
</div>

</body>
</html>