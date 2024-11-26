<?php
include 'db_connection.php';

if (isset($_GET['course_id'])) {
    $course_id = $_GET['course_id'];

    // Fetch students enrolled in the selected course
    $stmt = $pdo->prepare("
        SELECT s.id, s.name 
        FROM students s 
        JOIN enrollments e ON s.id = e.student_id 
        WHERE e.course_id = ?
    ");
    $stmt->execute([$course_id]);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($students as $student) {
        echo '<option value="' . htmlspecialchars($student['id']) . '">' . htmlspecialchars($student['name']) . '</option>';
    }
}
?>
