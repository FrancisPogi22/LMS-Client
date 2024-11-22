<?php
// Include database connection file
require 'db_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Insert data into admins table
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
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f7f7f7;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .container {
            background-color: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            font-weight: bold;
            color: #666;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="submit"],
        input[type="text"][id="code"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Admin Registration</h2>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
            
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            
            <input type="submit" value="Register">
        </form>
        <div class="footer">
            <p>Already have an account? <a href="admin_login.php">Login here</a></p>
        </div>
    </div>
    </div>

    
</body>
</html>
