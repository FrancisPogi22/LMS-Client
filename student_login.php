<?php
require 'db_connection.php';

session_start();

$message = '';

// Flags for SweetAlert conditions
$incorrect_login = false;
$account_pending = false;
$invalid_password = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = htmlspecialchars(trim($_POST['email']));
    $access_code = htmlspecialchars(trim($_POST['code']));
    $password = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $pdo->prepare("SELECT * FROM students WHERE email = ? AND code = ?");
    $stmt->execute([$email, $access_code]);
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        if ($student['approved'] == 1) {
            // Check password with case sensitivity using password_verify
            if (password_verify($password, $student['password'])) {
                // Store necessary session variables
                $_SESSION['student_id'] = $student['id'];
                $_SESSION['student_username'] = $student['username'];
                $_SESSION['student_name'] = $student['name'];
                $_SESSION['student_email'] = $student['email'];
                
                // Redirect to student courses page
                header("Location: Student_courses.php");
                exit();
            } else {
                // Flag for invalid password
                $invalid_password = true;
            }
        } else {
            // Flag for account pending approval
            $account_pending = true;
        }
    } else {
        // Flag for incorrect login (email or code not found)
        $incorrect_login = true;
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
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <style>
        .password-container {
            position: relative;
        }

        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            font-size: 18px;
            color: #888;
        }
    </style>
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
                            <input type="email" id="email" name="email" placeholder="Enter email" required>
                        </div>
                        <div class="field-container">
                            <label for="code">Access Code:</label>
                            <input type="text" id="code" name="code" placeholder="Enter code" required>
                        </div>
                        <div class="field-container password-container">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" placeholder="Enter password" required><br>
                            <span class="toggle-password" onclick="togglePassword()">👁️</span>
                        </div>
                        <div class="btn-container">
                            <button type="submit" class="btn-primary">Login</button>
                        </div>
                    </form>
                    <div class="forgot-password-link">
                        <p>Don't have an account? <a href="student_register.php">Register here</a></p>
                        <a href="forgotPassword.php" class="forgot-password">Forgot Password?</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function togglePassword() {
            const passwordField = document.getElementById("password");
            const toggleIcon = document.querySelector(".toggle-password");

            if (passwordField.type === "password") {
                passwordField.type = "text";
                toggleIcon.textContent = "👁"; // Change icon to 'hide' style
            } else {
                passwordField.type = "password";
                toggleIcon.textContent = "👁️"; // Change icon to 'show' style
            }
        }

        // Check if flags are set by PHP for login errors
        <?php if ($incorrect_login): ?>
            window.onload = function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Account not found',
                    text: 'Please check your email and access code.',
                    confirmButtonText: 'Ok'
                });
            };
        <?php elseif ($invalid_password): ?>
            window.onload = function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Invalid Password',
                    text: 'The password you entered is incorrect.',
                    confirmButtonText: 'Ok'
                });
            };
        <?php elseif ($account_pending): ?>
            window.onload = function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Account Pending Approval',
                    text: 'Your account is pending approval by the admin. Please try again later.',
                    confirmButtonText: 'Ok'
                });
            };
        <?php endif; ?>
    </script>
</body>

</html>
