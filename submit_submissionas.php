<?php
require_once 'db_connection.php';
header('Content-Type: application/json');
$assessment_id = $_POST['assessment_id'];
$student_id = $_POST['student_id'];
$course_id = $_POST['course_id'];
$submission_text = $_POST['submission_text'];
$stmt_check = $pdo->prepare("
    SELECT COUNT(*) FROM assessment_submissions 
    WHERE assessment_id = ? AND student_id = ?
");
$stmt_check->execute([$assessment_id, $student_id]);
$existing_submission = $stmt_check->fetchColumn();

if ($existing_submission > 0) {
    echo json_encode(['status' => 'error', 'message' => 'You have already submitted this assessment.']);
    exit;
}

$stmt = $pdo->prepare("
    INSERT INTO assessment_submissions (assessment_id, student_id, course_id, submission_text, created_at)
    VALUES (?, ?, ?, ?, NOW())
");
$stmt->execute([$assessment_id, $student_id, $course_id, $submission_text]);

if ($stmt) {
    echo json_encode(['status' => 'success', 'message' => 'Your submission has been successfully recorded!']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'There was an error processing your submission.']);
}
