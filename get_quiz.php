<?php
require 'db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['course_id']) && is_numeric($_GET['course_id'])) {
    $course_id = intval($_GET['course_id']);

    $query = "
        SELECT
            z.quiz_title,
            q.id AS question_id,
            q.question_text,
            o.id AS option_id,
            o.option_text,
            o.is_correct
        FROM
            quiz z
        LEFT JOIN questions q ON z.id = q.quiz_id
        LEFT JOIN options o ON q.id = o.question_id
        WHERE
            z.course_id = :course_id
    ";

    try {
        $stmt = $pdo->prepare($query);
        $stmt->bindValue(':course_id', $course_id, PDO::PARAM_INT);
        $stmt->execute();

        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $quiz_data = [];
        $quiz_title = null;
        foreach ($result as $row) {
            $quiz_title = $row['quiz_title'];
            $question_id = $row['question_id'];
            $quiz_data[$question_id]['question'] = $row['question_text'];
            $quiz_data[$question_id]['question_id'] = $row['question_id'];

            if ($row['is_correct'] == 1) {
                $quiz_data[$question_id]['correct_answer'] = $row['option_id'];
            }

            if ($row['option_id']) {
                $quiz_data[$question_id]['options'][] = [
                    'option_id' => $row['option_id'],
                    'option_text' => $row['option_text']
                ];
            }
        }

        if (!empty($quiz_data)) {
            echo json_encode([
                'question_id' => $question_id,
                'quiz_title' => $quiz_title,
                'questions' => array_values($quiz_data)
            ]);
        } else {
            echo json_encode(['error' => 'No quiz found for the given course ID.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'Invalid or missing course ID.']);
}
