<?php
require 'db_connection.php';

session_start();

$message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['email']);
    $access_code = htmlspecialchars($_POST['code']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND code = ?");
    $stmt->execute([$email, $access_code]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        if ($student['approved'] == 1) {
            if (password_verify($password, $student['password'])) {
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_username'] = $student['username'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['student_email'] = $student['email'];
                header("Location: Student_courses.php");
                exit();
            } else {
                $message = "<p style='color:red;'>Invalid password.</p>";
            }
        } else {
            $message = "<p style='color:red;'>Your account is pending approval by the admin. Please try again later.</p>";
        }
    } else {
        $message = "<p style='color:red;'>Account not found. Check your email and access code.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login</title>
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./assets/student_login.css">
</head>

<body>
    <section id="student">
        <div class="wrapper">
            <div class="student-container">
                <div class="image-container">
                    <img src="./loginimages/image1.png" alt="Image1">
                </div>
                <div class="container">
                    <h1>Student Login</h1>
                    <form method="POST" action="">
                        <div class="field-container">
                            <label for="email">Email:</label>
                            <input type="text" id="email" name="email" placeholder="Enter email" required>
                        </div>
                        <div class="field-container">
                            <label for="code">Access Code:</label>
                            <input type="text" id="code" name="code" placeholder="Enter code" required>
                        </div>
                        <div class="field-container">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="btn-container">
                            <button type="submit" class="btn-primary">Login</button>
                        </div>
                    </form>
                    <div class="forgot-password-link">
                        <p>Don't have an account? <a href="student_register.php">Register here</a></p>
                        <a href="reset_password.php" class="forgot-password">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </section>


</body>

</html>