<?php
require 'db_connection.php';

session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM instructors WHERE email = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($instructor && password_verify($password, $instructor['password'])) {
            $_SESSION['instructor_id'] = $instructor['id'];
            $_SESSION['session_id'] = $instructor['id'];
            $_SESSION['instructor_name'] = $instructor['name'];
            $_SESSION['user_id'] = $instructor['id'];
            header("Location: instructor.php");
            exit();
        } else {
            echo "<p style='color:red;'>Invalid credentials.</p>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./assets/admin_login.css">
    <title>Instructor Login</title>
</head>

<body>
    <section id="admin">
        <div class="wrapper">
            <div class="admin-container">
                <h1>Instructor Login</h1>
                <form method="POST" action="">
                    <div class="field-container">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" required>
                    </div>
                    <div class="field-container">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    <div class="btn-container">
                        <button type="submit" class="btn-primary">Login</button>
                    </div>
                </form>
                <div class="forgot-password-link">
                    <a href="reset_password.php" class="forgot-password">Forgot Password?</a>
                </div>
            </div>
        </div>
    </section>
</body>

</html>