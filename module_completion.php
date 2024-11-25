<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['student_id'];
    $module_id = $_POST['module_id'];

    $stmt = $pdo->prepare("SELECT * FROM completed_modules WHERE student_id = ? AND module_id = ?");
    $stmt->execute([$student_id, $module_id]);
    if ($stmt->rowCount() === 0) {
        $insert = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
        $insert->execute([$student_id, $module_id]);
    }

    echo json_encode(['success' => true]);
}
