<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email']) && isset($_POST['role'])) {
    $userEmail = $_POST['email'];
    $role = $_POST['role'];

    echo forgotPassword($userEmail, $role);
}

function forgotPassword($userEmail, $role)
{
    require 'db_connection.php';

    $expiresUTC = (new DateTime('now', new DateTimeZone('UTC')))
        ->modify('+30 minutes')
        ->format('Y-m-d H:i:s');

    if ($role === 'admin') {
        $query = "SELECT id FROM admins WHERE email = ?";
    } elseif ($role === 'instructor') {
        $query = "SELECT id FROM instructors WHERE email = ?";
    } else {
        $query = "SELECT id FROM students WHERE email = ?";
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute([$userEmail]);

    $userId = $stmt->fetchColumn();
    if (!$userId) {
        return "No user found with that email address.";
    }

    $token = bin2hex(random_bytes(32));

    $stmt = $pdo->prepare("
        INSERT INTO password_resets (account_id, token, expires) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE token = ?, expires = ?");
    $stmt->execute([$userId, $token, $expiresUTC, $token, $expiresUTC]);

    $resetLink = "http://localhost/LMS/resetPassword.php?token=$token&role=$role";

    $subject = "Password Reset Request";
    $message = "Hello,\n\nYou requested a password reset. Please click the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 30 minutes.\n\nIf you didn't request this, you can ignore this email.\n\nBest regards,\nYour Website Team";

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'francistengteng10@gmail.com';
        $mail->Password = 'ayug qjpx tqdy wraj';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->setFrom('no-reply@yourwebsite.com', 'System');
        $mail->addAddress($userEmail);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->send();
        return "A password reset link has been sent to your email address.";
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}
