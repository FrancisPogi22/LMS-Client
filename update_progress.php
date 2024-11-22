<?php
// update_progress.php
session_start();
require_once 'db_connection.php';  // Assuming this is your database connection file

// Check for valid request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (isset($data['module_id']) && isset($data['checked'])) {
        $module_id = $data['module_id'];
        $checked = $data['checked'];
        $student_id = $_SESSION['student_id'];

        // Check if the student has already marked this module as completed
        if ($checked) {
            // Insert into completed_modules
            $stmt = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
            $stmt->execute([$student_id, $module_id]);
        } else {
            // Remove from completed_modules
            $stmt = $pdo->prepare("DELETE FROM completed_modules WHERE student_id = ? AND module_id = ?");
            $stmt->execute([$student_id, $module_id]);
        }

        // Return success response
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid input']);
    }
}


?>