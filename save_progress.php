<?php
// Database connection
$host     = 'localhost:3306';
$username = 'root';
$password = '';
$dbname   = 'ics_db';
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get data from the AJAX request
$student_id = $_POST['student_id'];
$course_id = $_POST['course_id'];
$completed_modules = json_decode($_POST['completed_modules']); // Convert JSON string to PHP array

// Validate input
if (!empty($student_id) && !empty($course_id)) {
    // Example query to update the progress
    $completed_modules_str = implode(',', $completed_modules); // Convert array to comma-separated string

    // Insert or update the progress in the database
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
?>
