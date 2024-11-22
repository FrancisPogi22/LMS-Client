<?php
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = htmlspecialchars($_POST['name']);
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $code = htmlspecialchars($_POST['code']);

    $stmt = $pdo->prepare("INSERT INTO students (name, username, email, password, code, approved) VALUES (?, ?, ?, ?, ?, 0)");
    if ($stmt->execute([$name, $username, $email, $password, $code])) {
        echo "<script type='text/javascript'>alert('Registration successful! Your account is pending approval.'); window.location.href = 'student_login.php';</script>";
    } else {
        echo "<script type='text/javascript'>alert('Registration failed! Please try again.');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration</title>
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
                    <h1>Student Registration</h1>
                    <form method="POST" action="">
                        <div class="field-container">
                            <label for="name">Full Name:</label>
                            <input type="text" id="name" name="name" placeholder="Enter full name" required>
                        </div>
                        <div class="field-container">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" placeholder="Enter username" required>
                        </div>
                        <div class="field-container">
                            <label for="email">Email:</label>
                            <input type="email" id="email" name="email" placeholder="Enter email" required>
                        </div>

                        <div class="field-container">
                            <label for="password">Password:</label>
                            <input type="password" id="password" name="password" placeholder="Enter password" required>
                        </div>
                        <div class="field-container">
                            <label for="code">Access Code:</label>
                            <input type="text" id="code" name="code" required readonly>
                        </div>
                        <p class="code-info">Remember this access code; you will use it for login.</p>

                        <div class="btn-container">
                            <button type="submit" class="btn-primary">Register</button>
                        </div>
                    </form>
                    <div class="forgot-password-link">
                        <p>Already have an account <a href="student_login.php">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script>
        function generateCode() {
            var code = '',
                letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
                numbers = '0123456789';

            for (var i = 0; i < 3; i++) {
                var randomLetter = letters.charAt(Math.floor(Math.random() * letters.length));
                code += randomLetter;
            }

            for (var i = 0; i < 3; i++) {
                var randomNumber = numbers.charAt(Math.floor(Math.random() * numbers.length));
                code += randomNumber;
            }

            return code;
        }

        document.getElementById('code').value = generateCode();
    </script>
</body>

</html>