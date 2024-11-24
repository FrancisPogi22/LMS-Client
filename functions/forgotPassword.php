<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    $userEmail = $_POST['email'];

    echo forgotPassword($userEmail);
}

function forgotPassword($userEmail)
{
    include "../connectDb.php";

    date_default_timezone_set('Asia/Manila');
    $currentTimeManila = new DateTime("now", new DateTimeZone('Asia/Manila'));
    $currentTimeManila->modify("+30 minutes");
    $currentTimeManila->setTimezone(new DateTimeZone('UTC'));
    $expiresUTC = getExpireTimeInManila($currentTimeManila->format('Y-m-d H:i:s'));
    $stmt = $conn->prepare("SELECT account_id FROM account WHERE email = ?");
    $stmt->bind_param("s", $userEmail);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 0) return "No user found with that email address.";

    $stmt->bind_result($userId);
    $stmt->fetch();
    $stmt->close();
    $token = bin2hex(random_bytes(32));
    $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires) VALUES (?, ?, ?)
                            ON DUPLICATE KEY UPDATE token=?, expires=?");
    $stmt->bind_param("isssi", $userId, $token, $expiresUTC, $token, $expiresUTC);
    $stmt->execute();
    $stmt->close();
    $resetLink = "http://localhost/lms_code/reset_password.php?token=$token";
    $subject = "Password Reset Request";
    $message = "Hello,\n\nYou requested a password reset. Please click the link below to reset your password:\n\n$resetLink\n\nThis link will expire in 30 minutes.\n\nIf you didn't request this, you can ignore this email.\n\nBest regards,\nYour Website Team";
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'hatake92011@gmail.com';
        $mail->Password = 'syvf imzr bcwy btzu';
        $mail->SMTPSecure = 'ssl';
        $mail->Port = 465;
        $mail->setFrom('no-reply@yourwebsite.com', 'Your Website');
        $mail->addAddress($userEmail);
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->send();
        return "A password reset link has been sent to your email address.";
    } catch (Exception $e) {
        return "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
    }
}

function getExpireTimeInManila($expires)
{
    $utcTime = new DateTime($expires, new DateTimeZone('UTC'));
    $manilaTimeZone = new DateTimeZone('Asia/Manila');
    $utcTime->setTimezone($manilaTimeZone);

    return $utcTime->format('Y-m-d H:i:s');
}
