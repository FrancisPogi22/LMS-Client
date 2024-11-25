<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Submission Successful</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        window.onload = function() {
            Swal.fire({
                title: 'Submission Successful!',
                text: 'Your assessment has been successfully submitted.',
                icon: 'success',
                confirmButtonText: 'Go Back',
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'courses.php';
                }
            });
        };
    </script>
</head>
<body>
    <h1>Thank you for your submission!</h1>
    <p>Your assessment has been successfully submitted. You will be redirected shortly.</p>
    <p><a href="dashboard.php">Go to Dashboard</a></p>
</body>
</html>
