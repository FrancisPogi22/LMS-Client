<?php
session_start();
include 'db_connection.php';

if (isset($_POST['submit_reply'])) {
    $comment_id = $_POST['comment_id'];
    $reply_content = $_POST['reply_text'];
    $user_id = $_SESSION['user_id'];

    if (empty($comment_id)) {
        die('Error: Comment ID is missing.');
    }

    $stmt = $pdo->prepare("INSERT INTO replies (comment_id, user_id, reply_content) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $user_id, $reply_content]);

    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}
