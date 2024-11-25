<?php
$host     = 'localhost:3306';
$username = 'root';
$password = '';
$dbname   = 'ics_db';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$student_id = $_POST['student_id'];
$course_id = $_POST['course_id'];
$completed_modules = json_decode($_POST['completed_modules']);

if (!empty($student_id) && !empty($course_id)) {
    $completed_modules_str = implode(',', array: $completed_modules);
    $stmt = $conn->prepare("REPLACE INTO progress (student_id, course_id, completed_modules) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $student_id, $course_id, $completed_modules_str);
    
    if ($stmt->execute()) {
        echo "Progress saved successfully!";
    } else {
        echo "Error: " . $stmt->error;
    }
    
    $stmt->close();
} else {
    echo "Invalid data!";
}

$conn->close();
