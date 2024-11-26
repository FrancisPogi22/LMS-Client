<?php
session_start();
session_destroy(); // Destroy the session
header("Location: student_login.php"); // Redirect to login page
exit();
?>
