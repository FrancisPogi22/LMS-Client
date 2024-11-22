<?php
session_start();
$host = 'localhost';
$db_name = 'lms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$student_id = $_SESSION['student_id'];
$module_id = $_POST['module_id'];
$completed = $_POST['completed'] == 'true';

if ($completed) {
    $stmt = $pdo->prepare("INSERT IGNORE INTO completed_modules (student_id, module_id) VALUES (?, ?)");
    $stmt->execute([$student_id, $module_id]);
} else {
    $stmt = $pdo->prepare("DELETE FROM completed_modules WHERE student_id = ? AND module_id = ?");
    $stmt->execute([$student_id, $module_id]);
}

$course_id = $_GET['course_id']; // adjust if needed to fetch the course ID from a session or passed variable
$stmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
$stmt->execute([$course_id]);
$total_modules = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN (SELECT id FROM modules WHERE course_id = ?)");
$stmt->execute([$student_id, $course_id]);
$completed_modules = $stmt->fetchColumn();

$progress = $total_modules > 0 ? ($completed_modules / $total_modules) * 100 : 0;

echo json_encode(['progress' => $progress]);
?>
