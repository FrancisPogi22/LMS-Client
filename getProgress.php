<?php

function getProgress($student_id, $course_id, $pdo)
{
    $totalModulesQuery = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
    $totalModulesQuery->execute([$course_id]);
    $totalModules = $totalModulesQuery->fetchColumn();

    $completedModulesQuery = $pdo->prepare("
        SELECT COUNT(*) 
        FROM completed_modules cm
        JOIN modules m ON cm.module_id = m.id
        WHERE cm.student_id = ? AND m.course_id = ?
    ");
    $completedModulesQuery->execute([$student_id, $course_id]);
    $completedModules = $completedModulesQuery->fetchColumn();

    if ($totalModules > 0) {
        return ($completedModules / $totalModules) * 100;
    }
    
    return 0;
}
