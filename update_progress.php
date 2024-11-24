<?php
session_start();
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['module_id']) && isset($data['checked'])) {
        $module_id = $data['module_id'];
        $checked = $data['checked'];
        $student_id = $_SESSION['student_id'];

        if ($checked) {
            $stmt = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $module_id]);
        } else {
            $stmt = $pdo->prepare("DELETE FROM completed_modules WHERE student_id = ? AND module_id = ?");
            $stmt->execute([$student_id, $module_id]);
        }

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}
