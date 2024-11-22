<?php
// Include the DB connection
require_once 'db_connection.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required POST data is provided
    if (isset($_POST['student_id'], $_POST['course_id'], $_POST['completed_modules'])) {
        $student_id = $_POST['student_id'];
        $course_id = $_POST['course_id'];
        $completed_modules = json_decode($_POST['completed_modules']);

        // Validate the completed_modules data
        if ($completed_modules === null || empty($completed_modules)) {
            echo "Error: No completed modules provided.";
            exit;
        }

        // Prepare the SQL query
        $query = "INSERT INTO course_progress (student_id, course_id, module_id, status) VALUES (?, ?, ?, 'completed')";
        if ($stmt = $conn->prepare($query)) {
            // Bind parameters and execute the statement for each completed module
            foreach ($completed_modules as $module_id) {
                $stmt->bind_param('iis', $student_id, $course_id, $module_id);  // 'i' for integer, 's' for string
                if (!$stmt->execute()) {
                    echo "Error: " . $stmt->error;
                    $stmt->close();
                    $conn->close();
                    exit;
                }
            }

            echo "Progress saved successfully.";
            $stmt->close();
        } else {
            echo "Error: Could not prepare the SQL statement.";
        }

        // Close the database connection
        $conn->close();
    } else {
        echo "Error: Missing required POST data.";
    }
}
?>
