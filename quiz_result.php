<?php
session_start();
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['student_id'];
    $quiz_id = $_POST['quiz_id'];

    $query = $pdo->prepare("SELECT * FROM quiz_results WHERE student_id = ? AND quiz_id = ?");
    $query->execute([$student_id, $quiz_id]);
    $existing_result = $query->fetch(PDO::FETCH_ASSOC);

    if ($existing_result) {
        echo json_encode([
            'success' => true,
            'message' => 'You have already submitted this quiz.',
            'score' => $existing_result['score'],
            'total' => $existing_result['score']
        ]);
        exit;
    }

    $answers = $_POST['answers'];
    $total_question = 0;
    $score = 0;

    foreach ($answers as $question_id => $selected_option_id) {
        $query = $pdo->prepare("SELECT * FROM options WHERE question_id = ? AND is_correct = 1");
        $query->execute([$question_id]);
        $correct_option = $query->fetch(PDO::FETCH_ASSOC);
        $total_question++;
        if ($correct_option && $correct_option['id'] == $selected_option_id) {
            $score++;
        }
    }

    $insert = $pdo->prepare("INSERT INTO quiz_results (student_id, quiz_id, score, total) VALUES (?, ?, ?, ?)");
    $insert->execute([$student_id, $quiz_id, $score, $total_question]);

    echo json_encode([
        'success' => true,
        'score' => $score,
        'total' => count($answers)
    ]);
}
