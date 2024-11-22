<?php
// Include database connection file
require 'db_connection.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = htmlspecialchars($_POST['username']);
    $password = $_POST['password'];

    // Prepare and execute the query to fetch admin
    $stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
    $stmt->execute([$email]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify password and set session
    if ($admin && password_verify($password, $admin['password'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        header("Location: admin.php"); // Redirect to admin portal
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
    <title>Admin Login</title>
    <style>
        /* Fullscreen video background */
        body, html {
            height: 100%;
            margin: 0;
            font-family: Arial, sans-serif;
            overflow: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        video.background-video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.8); /* Semi-transparent background */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            position: relative;
            z-index: 1;
            text-align: center;
        }

        h2 {
            color: #333;
           
        }

        label {
            display: block;
            margin: 10px 0 5px;
            color: #666;
        }

        input[type="text"],
        input[type="password"],
        input[type="submit"] {
            width: 90%; /* Set width to 100% for all inputs */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            width: 50%;
            display: block;
            margin: 0 auto; 
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .register-link {
            text-align: center;
            margin-top: 10px;
        }

        .register-link a {
            color: #007bff;
            text-decoration: none;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .forgot-password-link {
            text-align: center;
            margin-top: 10px;
        }

        .forgot-password-link a {
            color: #007bff;
            text-decoration: none;
        }

        .forgot-password-link a:hover {
            text-decoration: underline;
        }

        .alert {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #f5c6cb;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <!-- Background video -->
    <video class="background-video" autoplay loop muted>
        <source src="/background/background3.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <div class="container">
        <h2>Admin Login</h2>

        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <input type="submit" value="Login">
        </form>
        
        <!-- Forgot Password Link -->
        <div class="forgot-password-link">
            <p style="text-align: center; margin-top: 10px;">
                <a href="reset_password.php" style="color: red; text-decoration: none;">Forgot Password?</a>
            </p>
        </div>
    </div>
</body>
</html>
