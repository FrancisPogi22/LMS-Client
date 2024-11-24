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
    <h2>Forgot Password</h2>
    <form action="./function/forgotPassword.php" method="POST">
        <label for="email">Enter your email address:</label>
        <input type="email" id="email" name="email" required>
        <button type="submit" class="btn btn-warning rounded-5">Reset Password</button>
    </form>
</body>

</html>