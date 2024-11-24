<?php
session_start();
require 'db_connection.php';

if (isset($_POST['assessment_id'], $_POST['comment'])) {
    $assessment_id = trim($_POST['assessment_id']);
    $comment = trim($_POST['comment']);

    if (empty($comment)) {
        echo "Comment cannot be empty.";
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO assessment_feedback (assessment_id, user_id, user_type, comment, created_at) 
            VALUES (:assessment_id, :user_id, :user_type, :comment, NOW())
        ");

        $stmt->execute([
            ':assessment_id' => $assessment_id,
            ':user_id' => $_SESSION['user_id'],
            ':user_type' => 'Instructor',
            ':comment' => $comment
        ]);

        echo "<script>
                alert('Comment submitted successfully.');
                window.location.href = 'instructor.php';
              </script>";
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
