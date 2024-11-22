<?php
session_start();
include 'db_connection.php'; // Ensure the DB connection is included

if (isset($_POST['submit_reply'])) {
    // Get the comment_id from the form submission
    $comment_id = $_POST['comment_id']; // The comment the reply is for
    $reply_content = $_POST['reply_text']; // The reply text content
    $user_id = $_SESSION['user_id']; // The user (instructor) replying

    // Make sure comment_id is not empty before inserting
    if (empty($comment_id)) {
        die('Error: Comment ID is missing.');
    }

    // Insert the reply into the replies table
    $stmt = $pdo->prepare("INSERT INTO replies (comment_id, user_id, reply_content) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $user_id, $reply_content]);

    // Redirect to the same page to refresh the view and show the reply
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
}

?>
