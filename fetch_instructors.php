<?php
// Include your database connection here
include 'db_connection.php';

$stmt = $pdo->query("SELECT * FROM instructors");
$instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($instructors as $instructor) {
    echo '<tr>';
    echo '<td>' . htmlspecialchars($instructor['name']) . '</td>';
    echo '<td>' . htmlspecialchars($instructor['gender']) . '</td>';
    echo '<td>' . htmlspecialchars($instructor['email']) . '</td>';
    echo '<td>';
    
    $courseStmt = $pdo->prepare("SELECT course_name FROM courses WHERE instructor_id = ?");
    $courseStmt->execute([$instructor['id']]);
    $course = $courseStmt->fetch(PDO::FETCH_ASSOC);
    echo $course ? htmlspecialchars($course['course_name']) : 'Not Assigned';
    
    echo '</td>';
    echo '<td>';
    echo '<button onclick="openEditModal(\'' . $instructor['id'] . '\', \'' . htmlspecialchars($instructor['name']) . '\', \'' . htmlspecialchars($instructor['email']) . '\', \'' . htmlspecialchars($instructor['gender']) . '\')" class="btn-edit">Edit</button>';
    echo '<form method="POST" action="delete_instructor.php" style="display:inline;">';
    echo '<input type="hidden" name="instructor_id" value="' . $instructor['id'] . '">';
    echo '<button type="submit" onclick="return confirm(\'Are you sure you want to delete this instructor?\');">Delete</button>';
    echo '</form>';
    echo '</td>';
    echo '</tr>';
}
?>
