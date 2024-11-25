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
                <h2>Forgot Password</h2>
                <form action="forgotPasswordFunc.php" method="POST">
                    <select name="role" id="">
                        <option value="admin">Admin</option>
                        <option value="instructor">Instructor</option>
                        <option value="student">Student</option>
                    </select>
                    <div class="field-container">
                        <label for="email">Enter your email address:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <button type="submit" class="btn btn-warning rounded-5">Reset Password</button>
                </form>
            </div>
        </div>
    </section>

</body>

</html>