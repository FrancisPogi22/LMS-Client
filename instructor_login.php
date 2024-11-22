<?php
// Include database connection file
require 'db_connection.php'; // Update with your actual DB connection file

session_start(); // Start a session

// Check if the form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize email input
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        // Prepare and execute the query to fetch instructor by email
        $stmt = $pdo->prepare("SELECT * FROM instructors WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify the password and log the user in
        if ($instructor && password_verify($password, $instructor['password'])) {
            // Set session variables
            $_SESSION['instructor_id'] = $instructor['id'];
            $_SESSION['instructor_name'] = $instructor['name'];
            $_SESSION['user_id'] = $instructor['id']; // Setting user_id to instructor_id for consistent usage
            header("Location: instructor.php"); // Redirect to the instructor dashboard
            exit();
        } else {
            echo "<p style='color:red;'>Invalid credentials.</p>";
        }
    } catch (PDOException $e) {
        // Handle any potential errors
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instructor Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .container {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #666;
        }
        input[type="email"],
        input[type="password"],
        input[type="submit"] {
            width: 90%; /* Set width to 100% for all inputs */
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        input[type="submit"] {
            background-color: #28a745;
            color: white;
            border: none;
            cursor: pointer;
            width: 50%;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Instructor Login</h2>
        <form method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>
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
