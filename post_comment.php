<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'];
    $post_id = $_POST['post_id'];

    if (empty($content)) {
        echo "Comment content is required!";
        exit();
    }

    $owner_id = $_SESSION['session_id'];
    $commentId = createComment($post_id, $owner_id, $content, $pdo);

    header("Location: forum.php");
    exit();
}

function createComment($post_id, $owner_id, $content, $pdo)
{
    $query = $pdo->prepare("INSERT INTO comments (post_id, owner_id, content, created_at) 
                            VALUES (?, ?, ?, NOW())");
    $query->execute([$post_id, $owner_id, $content]);

    return $pdo->lastInsertId();
}
