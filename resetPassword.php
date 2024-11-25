<?php
session_start();
require 'db_connection.php';

$token = $_GET['token'] ?? '';
$role = $_GET['role'] ?? '';

if ($token) {
    $stmt = $pdo->prepare("SELECT account_id FROM password_resets WHERE token = ?");
    $stmt->execute([$token]);
    $userId = $stmt->fetchColumn();

    if ($userId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password === $confirm_password) {
                if (strlen($new_password) < 8) {
                    echo "Password must be at least 8 characters long.";
                    exit;
                }

                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                if ($role === 'admin') {
                    $update_stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
                } elseif ($role === 'instructor') {
                    $update_stmt = $pdo->prepare("UPDATE instructors SET password = ? WHERE id = ?");
                } else {
                    $update_stmt = $pdo->prepare("UPDATE students SET password = ? WHERE id = ?");
                }

                $update_stmt->execute([$hashed_password, $userId]);

                $stmt = $pdo->prepare("DELETE FROM password_resets WHERE account_id = ?");
                $stmt->execute([$userId]);

                $_SESSION['success_message'] = "Your password has been reset successfully!";
                header("Location: admin_login.php");
                exit();
            } else {
                echo "Passwords do not match. Please try again.";
            }
        }
    } else {
        echo "Invalid or expired token.";
        exit;
    }
} else {
    echo "No token provided.";
    exit;
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <link rel="stylesheet" href="./assets/theme.css">
</head>

<body>
    <section>
        <div class="wrapper">
            <div class="reset-container">
                <h2>Reset Password</h2>
                <form action="" method="POST">
                    <div class="field-container">
                        <label for="password">New Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="field-container">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <button type="submit" class="btn-primary">Reset Password</button>
                </form>
            </div>
        </div>
    </section>
</body>

</html>