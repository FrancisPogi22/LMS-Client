<?php
require 'db_connection.php';

$post_id = $_POST['post_id'];
$student_id = $_POST['student_id'];
$content = $_POST['content'];

$stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, content, created_at) VALUES (?, ?, ?, NOW())");

if ($stmt->execute([$post_id, $student_id, $content])) {
    echo json_encode(["status" => "success", "message" => "Comment saved successfully!"]);
} else {
    echo json_encode(["status" => "error", "message" => "Failed to save the comment."]);
}
exit();
