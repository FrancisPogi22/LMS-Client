
<?php
// Include database connection file
require 'db_connection.php'; // Ensure to replace with your actual DB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize form data
    $name = htmlspecialchars($_POST['name']); // New input for name
    $username = htmlspecialchars($_POST['username']);
    $email = htmlspecialchars($_POST['email']);
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $code = htmlspecialchars($_POST['code']);

    // Insert data into students table with 'approved' set to 0 (not approved)
    $stmt = $pdo->prepare("INSERT INTO students (name, username, email, password, code, approved) VALUES (?, ?, ?, ?, ?, 0)"); // Include 'approved' column as 0
    if ($stmt->execute([$name, $username, $email, $password, $code])) {
        // If registration is successful, alert the user
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
    <title>Student Registration</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            flex-direction: column;
        }
        .navbar {
            width: 25%;
            background-color: #007bff;
            padding: 15px 0;
            text-align: center;
            margin-top: 5px;
            margin-left: 500px;
            border-radius: 40px;
        }
        .navbar a {
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 16px;
        }
        .navbar a:hover {
            background-color: #0056b3;
            border-radius: 4px;
        }
        .main-container {
            display: flex;
            width: 900px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-top: 10px;
            height: 100vh;
            
        }
        .image-container {
            width: 80%;
            background-image: url('./loginimages/image.png'); /* Replace with your image path */
            background-size: cover;
            background-position: center;
            height: 100vh;
        }
        .container {
            width: 50%;
            padding: 20px;
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
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="submit"] {
            width: 90%;
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
            display: block;
            margin: 0 auto;
        }
        input[type="submit"]:hover {
            background-color: #218838;
        }
        .footer {
            text-align: center;
            margin-top: 10px;
        }
        .footer a {
            color: #007bff;
            text-decoration: none;
        }
        .footer a:hover {
            text-decoration: underline;
        }
        .code-info {
            color: #cc0808;
            font-size: 14px;
            text-align: center;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <div class="navbar">
        <a href="student_login.php">Login</a>
        <a href="student_register.php">Register</a>
    </div>

    <!-- Main Content with Image and Registration Form -->
    <div class="main-container">
        <div class="image-container"></div>
        <div class="container">
            <h2>Student Registration</h2>
            <form method="POST" action="">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="code">Access Code:</label>
                <input type="text" id="code" name="code" required readonly>

                <p class="code-info">Remember this access code; you will use it for login.</p>

                <input type="submit" value="Register">
            </form>

            <!-- Login Link -->
            <div class="footer">
                <p>Already have an account? <a href="student_login.php">Login here</a></p>
            </div>
        </div>
    </div>

    <script>
        // Function to generate a 3 uppercase letter followed by 3 digits access code
        function generateCode() {
            var code = '';
            var letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
            var numbers = '0123456789';

            // Generate 3 random uppercase letters
            for (var i = 0; i < 3; i++) {
                var randomLetter = letters.charAt(Math.floor(Math.random() * letters.length)); 
                code += randomLetter;
            }

            // Generate 3 random numbers
            for (var i = 0; i < 3; i++) {
                var randomNumber = numbers.charAt(Math.floor(Math.random() * numbers.length));
                code += randomNumber;
            }

            return code;
        }

        // Assign the generated code to the access code input field
        document.getElementById('code').value = generateCode();
    </script>
</body>
</html>
