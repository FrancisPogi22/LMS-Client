<?php
require 'db_connection.php';
require 'getProgress.php';

$input = json_decode(file_get_contents('php://input'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($input['module_id'], $input['student_id'])) {
    $module_id = $input['module_id'];
    $student_id = $input['student_id'];
    $course_id = $input['course_id'];

    $query = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id = ?");
    $query->execute([$student_id, $module_id]);
    $is_completed = $query->fetchColumn();

    if (!$is_completed) {
        $insert = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
        $insert->execute([$student_id, $module_id]);
    }

    $progress = getProgress($student_id, $course_id, $pdo);

    echo json_encode([
        'success' => true,
        'message' => 'Module marked as complete.',
        'progress' => $progress,
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.',
    ]);
}
