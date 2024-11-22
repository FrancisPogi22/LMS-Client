<?php
session_start();
require 'db_connection.php'; // Include your PDO database connection here

// Initialize an error or success message variable
$message = '';

if (isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $student_id = $_SESSION['student_id']; // Assuming student_id is stored in session upon login

    // Check if new password and confirm password match
    if ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
    } else {
        // Fetch the current password from the database
        $stmt = $pdo->prepare("SELECT password FROM students WHERE id = :id");
        $stmt->execute(['id' => $student_id]);
        $stored_password = $stmt->fetchColumn();

        // Verify the current password
        if ($stored_password && password_verify($current_password, $stored_password)) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the database
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
    <!-- Include SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .change-password-form {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .change-password-form h2 {
            text-align: center;
        }
        .change-password-form input[type="password"],
        .change-password-form button {
            width: 100%;
            padding: 10px;
            margin-top: 10px;
        }
        .success, .error {
            text-align: center;
            margin-top: 10px;
        }
        .success { color: green; }
        .error { color: red; }
        .button-link {
            text-align: center;
            margin-top: 20px;
        }
        .button-link a {
            display: inline-block;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }
        .button-link a:hover {
            background-color: #0056b3;
        }
    </style>
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
        <a href="profile_students.php">Go Back</a> <!-- Replace with your actual page URL -->
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
            window.location.href = 'change_password.php'; // Redirect after clicking OK
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
