<?php
require 'db_connection.php';

$message = '';
$alert_type = '';
$redirect_url = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $new_password = $_POST['password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $user_tables = [
        'admins' => 'admin_login.php',
        'instructors' => 'instructor_login.php',
        'students' => 'student_login.php'
    ];
    $password_updated = false;

    foreach ($user_tables as $table => $login_page) {
        $stmt = $pdo->prepare("UPDATE $table SET password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);

        if ($stmt->rowCount() > 0) {
            $password_updated = true;
            $redirect_url = $login_page;
            break;
        }
    }

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./assets/admin_login.css">
</head>

<body>
    <section id="admin">
        <div class="wrapper">
            <div class="admin-container">
                <h1>Reset Password</h1>
                <?php if ($message): ?>
                    <div class="alert <?= $alert_type; ?>">
                        <?= $message; ?>
                    </div>
                    <?php if ($password_updated): ?>
                        <script>
                            setTimeout(function() {
                                window.location.href = '<?= $redirect_url; ?>';
                            }, 3000);
                        </script>
                    <?php endif; ?>
                <?php endif; ?>
                <form method="POST" action="">
                    <div class="field-container">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" placeholder="Enter email" required>
                    </div>
                    <div class="field-container">
                        <label for="password">New Password:</label>
                        <input type="password" id="password" name="password" placeholder="Enter new password" required>
                    </div>
                    <div class="btn-container">
                        <button type="submit" class="btn-primary">Reset Password</button>
                    </div>
                </form>
            </div>
        </div>
    </section>

</body>

</html>