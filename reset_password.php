<?php
// Include database connection file
require 'db_connection.php';

$message = '';
$alert_type = '';
$redirect_url = ''; // Variable to store the redirect URL for each user type

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = $_POST['password'];

    // Hash the new password before storing it
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

    // Define user tables and their corresponding login pages
    $user_tables = [
        'admins' => 'admin_login.php',
        'instructors' => 'instructor_login.php',
        'students' => 'student_login.php'
    ];
    $password_updated = false;

    // Loop through each table and attempt to update password
    foreach ($user_tables as $table => $login_page) {
        $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);

        if ($stmt->rowCount() > 0) {
            $password_updated = true;
            $redirect_url = $login_page; // Set the redirect URL based on user type
            break; // Stop after updating the first matching record
        }
    }

    // Check if the password was updated in any table
    if ($password_updated) {
        $message = "Password has been updated successfully!";
        $alert_type = "success";
    } else {
        $message = "Email not found or error updating password.";
        $alert_type = "error";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password</title>
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
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            width: 350px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin: 10px 0 5px;
            color: #666;
            text-align: left;
        }
        input[type="email"],
        input[type="password"] {
            width: 90%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            border: none;
            cursor: pointer;
            padding: 10px 0;
            width: 100%;
            border-radius: 4px;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .alert {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 4px;
            color: white;
        }
        .alert.success {
            background-color: #28a745;
        }
        .alert.error {
            background-color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Reset Password</h2>
        <?php if ($message): ?>
            <div class="alert <?= $alert_type; ?>">
                <?= $message; ?>
            </div>
            <?php if ($password_updated): ?>
                <script>
                    // Redirect to the specific login page after 3 seconds
                    setTimeout(function() {
                        window.location.href = '<?= $redirect_url; ?>';
                    }, 3000);
                </script>
            <?php endif; ?>
        <?php endif; ?>
        
        <form method="POST" action="">
            <label for="email">Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="password">New Password:</label>
            <input type="password" id="password" name="password" required>

            <input type="submit" value="Reset Password">
        </form>
    </div>
</body>
</html>
