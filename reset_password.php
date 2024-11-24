<?php
session_start();
include 'db_connection.php';

$token = $_GET['token'] ?? '';

if ($token) {
    $stmt = $conn->prepare("SELECT student_id FROM password_resets WHERE token = ? AND expires > NOW()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->bind_result($studentId);
    $stmt->fetch();
    $stmt->close();

    if ($userId) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE account SET user_password = ? WHERE account_id = ?");
                $update_stmt->bind_param("si", $hashed_password, $userId);
                $update_stmt->execute();
                $update_stmt->close();
                $stmt = $conn->prepare("DELETE FROM password_resets WHERE user_id = ?");
                $stmt->bind_param("i", $studentId);
                $stmt->execute();
                $stmt->close();

                $_SESSION['success_message'] = "Your password has been reset successfully!";
                header("Location: ./index.php");
                exit();
            } else
                echo "Passwords do not match. Please try again.";
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
    <link rel="stylesheet" href="css/loginStyle.css">
    <script src="js/eyeToggle.js"></script>
</head>

<body>
    <h2>Reset Password</h2>
    <form action="" method="POST">
        <label for="password">New Password:</label>
        <input type="password" id="password" name="password" required>
        <br>
        <label for="confirm_password">Confirm Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
        <br>
        <button type="submit" class="btn btn-warning rounded-5">Reset Password</button>
    </form>
</body>

</html>