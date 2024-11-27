<?php
require 'db_connection.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['course_id'], $_POST['quiz_title']) && is_numeric($_POST['course_id']) && isset($_POST['questions'])) {
    $course_id = intval($_POST['course_id']);
    $quiz_title = trim($_POST['quiz_title']);
    $questions = $_POST['questions'];

    if (empty($quiz_title) || !is_array($questions) || count($questions) === 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid quiz data.']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("
            INSERT INTO quiz (course_id, quiz_title)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE quiz_title = VALUES(quiz_title), id = LAST_INSERT_ID(id)
        ");
        $stmt->execute([$course_id, $quiz_title]);
        $quiz_id = $pdo->lastInsertId();

        foreach ($questions as $question) {
            $question_id = isset($question['question_id']) ? intval($question['question_id']) : null;

            if ($question_id) {
                $stmt = $pdo->prepare("UPDATE questions SET question_text = ? WHERE id = ? AND quiz_id = ?");
                $stmt->execute([$question['question'], $question_id, $quiz_id]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO questions (quiz_id, question_text) VALUES (?, ?)");
                $stmt->execute([$quiz_id, $question['question']]);
                $question_id = $pdo->lastInsertId();
            }

            foreach ($question['options'] as $optIndex => $option) {
                if (is_array($option) && isset($option['option_text'])) {
                    $option_text = trim($option['option_text']);
                    $option_id = isset($option['option_id']) ? intval($option['option_id']) : null;
                    $is_correct = ($optIndex == intval($question['correct_answer'])) ? 1 : 0; // Correct answer logic

                    if ($option_id) {
                        $stmt = $pdo->prepare("UPDATE options SET option_text = ?, is_correct = ? WHERE id = ?");
                        $stmt->execute([$option_text, $is_correct, $option_id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO options (question_id, option_text, is_correct) VALUES (?, ?, ?)");
                        $stmt->execute([$question_id, $option_text, $is_correct]);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Malformed option data.']);
                    exit;
                }
            }

            $stmt = $pdo->prepare("UPDATE options SET is_correct = 0 WHERE question_id = ?");
            $stmt->execute([$question_id]);
            $stmt = $pdo->prepare("UPDATE options SET is_correct = 1 WHERE question_id = ? AND option_text = ?");
            $stmt->execute([$question_id, $question['options'][intval($question['correct_answer'])]['option_text']]);
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => 'Quiz updated successfully.']);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Error saving quiz: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}
