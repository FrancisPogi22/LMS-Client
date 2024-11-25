<?php
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (username, email, password) VALUES (?, ?, ?)");
    if ($stmt->execute([$username, $email, $password])) {
        echo "<div class='alert'>Admin registration successful!</div>";
    } else {
        echo "<div class='alert alert-error'>Registration failed! This email might already be in use.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Registration</title>
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./assets/admin_login.css">
</head>

<body>
    <section id="admin">
        <div class="wrapper">
            <div class="admin-container">
                <h2>Admin Registration</h2>
                <form method="POST" action="">
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
                    <div class="btn-container">
                        <button type="submit" value="Login" class="btn-primary">Register</button>
                    </div>
                </form>
                <div class="forgot-password-link">
                    <p>Already have an account? <a href="admin_login.php">Login here</a></p>
                    <a href="reset_password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </div>
        </div>
    </section>
</body>

</html>