<?php
include 'db_connection.php';

if (isset($_GET['id']) && isset($_GET['action'])) {
    $studentId = $_GET['id'];
    $action = $_GET['action'];

    if ($pdo) {
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE students SET approved = 1 WHERE id = ?");
        } elseif ($action == 'deny') {
            $stmt = $pdo->prepare("UPDATE students SET approved = 0 WHERE id = ?");
        }

        $stmt->bindParam(1, $studentId, PDO::PARAM_INT);

        if ($stmt->execute()) {
            header("Location: admin.php?status=success&action=$action");
            exit();
        } else {
            header("Location: admin.php?status=error");
            exit();
        }
    } else {
        echo "Database connection failed.";
    }
} else {
    echo "Invalid request.";
}
