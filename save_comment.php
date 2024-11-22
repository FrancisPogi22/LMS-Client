<?php
// save_comment.php
require 'db_connection.php'; // Include your database connection file

// Capture form inputs
$post_id = $_POST['post_id'];  // This refers to 'id' from 'assessment_submission' table
$student_id = $_POST['student_id'];  // The student_id who is submitting the comment
$content = $_POST['content'];  // The content of the comment

// Insert the comment into the comments table
$stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, content, created_at) VALUES (?, ?, ?, NOW())");

if ($stmt->execute([$post_id, $student_id, $content])) {
    // Return a JSON response indicating success
    echo json_encode(["status" => "success", "message" => "Comment saved successfully!"]);
} else {
    // Return a JSON response indicating failure
    echo json_encode(["status" => "error", "message" => "Failed to save the comment."]);
}
exit();
?>
