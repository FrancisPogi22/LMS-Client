<?php
session_start();
require 'db_connection.php';
require 'vendor/tecnickcom/tcpdf/tcpdf.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = $_SESSION['student_id'] ?? null;
    $quiz_id = $_POST['quiz_id'] ?? null;
    $course_id = $_POST['course_id'] ?? null;

    if (!$student_id || !$quiz_id || !$course_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Missing required data: student ID, quiz ID, or course ID.'
        ]);
        exit;
    }

    $query = $pdo->prepare("
        SELECT q.*, s.username 
        FROM quiz_results q 
        LEFT JOIN students s ON s.id = q.student_id 
        WHERE q.student_id = ? AND q.quiz_id = ?
    ");
    $query->execute([$student_id, $quiz_id]);
    $existing_result = $query->fetch(PDO::FETCH_ASSOC);

    if ($existing_result) {
        echo json_encode([
            'success' => true,
            'message' => 'You have already submitted this quiz.',
            'score' => $existing_result['score'],
            'total' => $existing_result['total']
        ]);
        exit;
    }

    $answers = $_POST['answers'] ?? [];
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
    $course_query = $pdo->prepare("SELECT course_name FROM courses WHERE id = ?");
    $course_query->execute([$course_id]);
    $course = $course_query->fetch(PDO::FETCH_ASSOC);

    $query1 = $pdo->prepare("
        SELECT username 
        FROM students 
        WHERE id = ?
    ");
    $query1->execute([$student_id]);
    $existing_result1 = $query1->fetch(PDO::FETCH_ASSOC);

    if (!$course) {
        echo json_encode([
            'success' => false,
            'message' => 'Course not found.'
        ]);
        exit;
    }

    $pdf = new TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    $pdf->SetMargins(10, 10, 10);
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    $pdf->AddPage('L');
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Quiz App');
    $pdf->SetTitle('CERTIFICATE OF COMPLETION');
    $username = htmlspecialchars($existing_result1['username'] ?? 'Student Name');
    $course_name = htmlspecialchars($course['course_name']);

    $imagePath = 'images/admin_courses.png';
    $imageWidth = 20;
    $imageHeight = 20;
    $pageWidth = $pdf->GetPageWidth();
    $xPosition = ($pageWidth - $imageWidth) / 2;
    $pdf->Image($imagePath, $xPosition, 20, $imageWidth);

    $pdf->SetY(20 + $imageHeight + 10);

    $pdf->SetFont('helvetica', 'B', 40);
    $pdf->Cell(0, 10, 'CERTIFICATE OF COMPLETION', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'This Certificate is proudly presented to', 0, 1, 'C');

    $pdf->SetFont('helvetica', 'B', 50);
    $pdf->Cell(0, 10, $username, 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'For successfully completing the ' . $course_name . ' certification.', 0, 1, 'C');

    $pdf->SetFont('helvetica', '', 14);
    $pdf->Cell(0, 10, 'In witness whereof, we have subscribed our signatures under the seal of the company.', 0, 1, 'C');

    $pdf->SetY(175);
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'Issued on: ' . date('F j, Y'), 0, 0, 'L');

    $filename = "quiz_result_{$quiz_id}_{$student_id}.pdf";
    $directory = __DIR__ . "/pdfs/";
    if (!is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    $filepath = $directory . $filename;
    $pdf->Output($filepath, 'F');

    echo json_encode([
        'success' => true,
        'score' => $score,
        'total' => $total_question,
        'download_link' => "pdfs/" . $filename
    ]);
}
