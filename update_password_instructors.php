<?php
require 'db_connection.php';
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

if (isset($_POST['update_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password = $pdo->prepare("UPDATE instructors SET password = ? WHERE id = ?");
        $update_password->execute([$hashed_password, $instructor_id]);
        $_SESSION['successMessage'] = "Password updated successfully!";
        header("Location: instructor.php");
        exit();
    } else {
        $_SESSION['errorMessage'] = "Passwords do not match!";
    }
}

header("Location: instructor.php");
exit();
