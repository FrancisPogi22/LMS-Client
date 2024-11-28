<?php

function getQuizProgress($student_id, $course_id, $pdo)
{
    $quizResultsQuery = $pdo->prepare("
        SELECT SUM(score) AS total_score, SUM(total) AS total_questions
        FROM quiz_results
        WHERE student_id = ? AND quiz_id IN (SELECT id FROM quiz WHERE course_id = ?)
    ");
    $quizResultsQuery->execute([$student_id, $course_id]);
    $quizResults = $quizResultsQuery->fetch(PDO::FETCH_ASSOC);

    $totalScore = $quizResults['total_score'];
    $totalQuestions = $quizResults['total_questions'];

    if ($totalQuestions > 0) {
        return ($totalScore / $totalQuestions) * 100;
    }

    return 0;
}
