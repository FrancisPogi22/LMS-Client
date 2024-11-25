<?php
session_start();
require 'db_connection.php';

$message = '';

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = $_SESSION['student_id'];

    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT password FROM students WHERE id = :id");
        $stmt->execute(['id' => $student_id]);
        $stored_password = $stmt->fetchColumn();

        if ($stored_password && password_verify($current_password, $stored_password)) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $pdo->prepare("UPDATE students SET password = :password WHERE id = :id");

            if ($update_stmt->execute(['password' => $hashed_password, 'id' => $student_id])) {
                $message = "Password has been updated successfully.";
                $is_success = true;
            } else {
                $message = "Error updating password. Please try again.";
                $is_success = false;
            }
        } else {
            $message = "Current password is incorrect.";
            $is_success = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="./assets/change_pass.css">
</head>

<body>
    <div class="change-password-form">
        <h2>Change Password</h2>
        <?php if (!empty($message)): ?>
            <p class="<?php echo isset($is_success) && $is_success ? 'success' : 'error'; ?>">
                <?php echo $message; ?>
            </p>
        <?php endif; ?>
        <form action="change_password.php" method="POST">
            <label for="current_password">Current Password</label>
            <input type="password" id="current_password" name="current_password" required>
            <label for="new_password">New Password</label>
            <input type="password" id="new_password" name="new_password" required>
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required>
            <button type="submit" name="change_password">Update Password</button>
        </form>
        <div class="button-link">
            <a href="profile_students.php">Go Back</a>
        </div>
    </div>

    <script>
        <?php if (isset($is_success) && $is_success): ?>
            Swal.fire({
                icon: 'success',
                title: 'Password Changed!',
                text: 'Your password has been updated successfully.',
                confirmButtonText: 'OK'
            }).then(function() {
                window.location.href = 'change_password.php';
            });
        <?php elseif (isset($is_success) && !$is_success): ?>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $message; ?>',
                confirmButtonText: 'OK'
            });
        <?php endif; ?>
    </script>

</body>

</html>