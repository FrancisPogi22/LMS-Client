<?php
require 'db_connection.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['course_id'])) {
        echo json_encode(['error' => 'Course ID not provided.']);
        exit;
    }

    $course_id = $_GET['course_id'];

    $query = $pdo->prepare("
        SELECT 
        s.username AS student_name, 
        qr.student_id, 
        qr.score, 
        qr.total, 
        q.quiz_title 
    FROM 
        quiz_results qr
    JOIN 
        quiz q ON qr.quiz_id = q.id
    JOIN 
        students s ON qr.student_id = s.id
    WHERE 
        q.course_id = ?
    ");
    $query->execute([$course_id]);
    $results = $query->fetchAll(PDO::FETCH_ASSOC);

    if ($results) {
        echo json_encode($results);
    } else {
        echo json_encode(['error' => 'No quiz results found for this course.']);
    }
} catch (Exception $e) {
    echo json_encode(['error' => 'An error occurred: ' . $e->getMessage()]);
}
