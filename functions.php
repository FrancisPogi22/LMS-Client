<?php
if (!function_exists('getStudentProgress')) {
    function getStudentProgress($student_id, $course_id) {
        global $pdo;
        $progress = 75; 
        return $progress;
    }
}
