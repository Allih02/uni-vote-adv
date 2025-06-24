<?php
session_start();
if (!isset($_SESSION['studentId'])) {
    header('Location: index.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Candidates</title>
</head>
<body>
    <h1>Candidates</h1>
    <ul>
        <li>Candidate 1 - Position</li>
        <li>Candidate 2 - Position</li>
    </ul>
</body>
</html>
