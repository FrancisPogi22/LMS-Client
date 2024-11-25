<?php
// Declare the function only if it's not already declared
if (!function_exists('getStudentProgress')) {
    function getStudentProgress($student_id, $course_id) {
        global $pdo;
        // Your code for calculating student progress
        $progress = 75;  // Example value
        return $progress;
    }
}
?>
