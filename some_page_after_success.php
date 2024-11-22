<?php
// Start the session if needed (if you have session variables)
session_start();

// Optionally, you can include a check here to ensure the user is authenticated or has access
// Example: if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Successful</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Trigger SweetAlert on page load to show success message
        window.onload = function() {
            Swal.fire({
                title: 'Submission Successful!',
                text: 'Your assessment has been successfully submitted.',
                icon: 'success',
                confirmButtonText: 'Go Back',
            }).then((result) => {
                if (result.isConfirmed) {
                    // Redirect the user to another page after confirming the SweetAlert button
                    window.location.href = 'courses.php';  // Change to the page you want to redirect to
                }
            });
        };
    </script>
</head>
<body>
    <h1>Thank you for your submission!</h1>
    <p>Your assessment has been successfully submitted. You will be redirected shortly.</p>

    <!-- Optionally add a link for the user to manually go back -->
    <p><a href="dashboard.php">Go to Dashboard</a></p>
</body>
</html>
