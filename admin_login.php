<?php
require 'db_connection.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: admin.php");
        exit();
    } else {
        echo "<div class='alert'>Invalid credentials.</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./assets/admin_login.css">
</head>

<body>
    <section id="admin">
        <div class="wrapper">
            <div class="admin-container">
                <!-- <video class="background-video" autoplay loop muted>
                    <source src="/background/background3.mp4" type="video/mp4">
                    Your browser does not support the video tag.
                </video> -->
                <h1>Admin Login</h1>
                <form method="POST" action="">
                    <div class="field-container">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" placeholder="Enter username" required>
                    </div>
                    <div class="field-container">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter password" required>
                    </div>
                    <div class="btn-container">
                        <button type="submit" value="Login" class="btn-primary">Login</button>
                    </div>
                </form>
                <div class="forgot-password-link">
                    <a href="reset_password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </div>
        </div>
    </section>
</body>

</html>