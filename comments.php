<?php
require 'db_connection.php';

// Fetch submission ID from the URL or form
$submission_id = $_GET['submission_id'] ?? null;
$user_id = 1; // Replace with session value (can be student or instructor)
$user_type = 'student'; // Adjust based on session value (either 'student' or 'instructor')

// Handle feedback/comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment'])) {
    $comment = $_POST['comment'];

    $stmt = $pdo->prepare("INSERT INTO assignment_feedback (submission_id, user_id, user_type, comment) VALUES (?, ?, ?, ?)");
    $stmt->execute([$submission_id, $user_id, $user_type, $comment]);

    echo "Comment posted successfully!";
}

// Fetch all comments for a specific submission
$comments = $pdo->prepare("SELECT * FROM assignment_feedback WHERE submission_id = ?");
$comments->execute([$submission_id]);
$feedback = $comments->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments and Feedback</title>
</head>
<body>
    <h1>Comments and Feedback</h1>

    <form method="POST">
        <textarea name="comment" placeholder="Write a comment" required></textarea>
        <button type="submit">Post Comment</button>
    </form>

    <h2>Feedback Thread</h2>
    <ul>
        <?php foreach ($feedback as $comment): ?>
            <li><strong><?= htmlspecialchars($comment['user_type']) ?>:</strong> <?= htmlspecialchars($comment['comment']) ?> - <?= $comment['created_at'] ?></li>
        <?php endforeach; ?>
    </ul>
</body>
</html>
