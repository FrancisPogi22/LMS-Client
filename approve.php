<?php
// approve.php
include 'db_connection.php'; // Include the database connection

if (isset($_GET['id']) && isset($_GET['action'])) {
    $studentId = $_GET['id'];
    $action = $_GET['action'];

    // Ensure PDO connection is available
    if ($pdo) {
        // Prepare the SQL query depending on the action (approve/deny)
        if ($action == 'approve') {
            $stmt = $pdo->prepare("UPDATE students SET approved = 1 WHERE id = ?");
        } elseif ($action == 'deny') {
            $stmt = $pdo->prepare("UPDATE students SET approved = 0 WHERE id = ?");
        }

        // Bind the student ID parameter to the prepared statement
        $stmt->bindParam(1, $studentId, PDO::PARAM_INT);

        // Execute the query
        if ($stmt->execute()) {
            // Redirect back to the student list page with a success message
            header("Location: admin.php?status=success&action=$action");
            exit();
        } else {
            // Redirect with an error message if the update fails
            header("Location: admin.php?status=error");
            exit();
        }
    } else {
        echo "Database connection failed.";
    }
} else {
    echo "Invalid request.";
}
?>