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

if (!isset($_SESSION['student_id'])) {
    die("Error: Student ID not found in session.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $student_id = $_SESSION['student_id']; 
    $assessment_title = $_POST['assessment_title'];
    $assessment_description = $_POST['assessment_description'];

    if (empty($student_id)) {
        die("Error: Student ID cannot be empty.");
    }

    $sql = "INSERT INTO assessment_submissions (course_id, student_id, assessment_title, assessment_description) 
            VALUES (:course_id, :student_id, :assessment_title, :assessment_description)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':course_id', $course_id);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->bindParam(':assessment_title', $assessment_title);
    $stmt->bindParam(':assessment_description', $assessment_description);

    if ($stmt->execute()) {
        echo "Assessment saved successfully.";
    } else {
        echo "Error: Could not save the assessment.";
    }
}
