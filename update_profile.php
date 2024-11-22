<?php
// Include database connection
require 'db_connection.php';
session_start();

if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php");
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

if (isset($_POST['update_picture']) && isset($_FILES['profile_picture'])) {
    $file_name = $_FILES['profile_picture']['name'];
    $file_tmp = $_FILES['profile_picture']['tmp_name'];
    $file_error = $_FILES['profile_picture']['error'];

    if ($file_error === 0) {
        $upload_dir = 'uploads/profile_picture/';
        $new_file_path = $upload_dir . basename($file_name);

        if (move_uploaded_file($file_tmp, $new_file_path)) {
            // Update the profile picture in the database
            $update_picture = $pdo->prepare("UPDATE instructors SET profile_picture = ? WHERE id = ?");
            $update_picture->execute([$new_file_path, $instructor_id]);

            // Redirect to the profile page with a success message
            $_SESSION['successMessage'] = "Profile picture updated successfully!";
            header("Location: instructor.php"); // Adjust the location accordingly
            exit();
        } else {
            $_SESSION['errorMessage'] = "Failed to upload profile picture.";
        }
    } else {
        $_SESSION['errorMessage'] = "Error uploading the picture.";
    }
}

header("Location: instructor.php"); // Adjust the location accordingly
exit();
?>
