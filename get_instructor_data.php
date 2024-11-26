<?php
include 'db_connection.php';

if (isset($_GET['id'])) {
    $instructor_id = $_GET['id'];

    $stmt = $pdo->prepare("SELECT id, name, email, course_id, gender, profile_picture FROM instructors WHERE id = ?");
    $stmt->execute([$instructor_id]);

    $instructor = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');
    echo json_encode($instructor);
}
?>
