<?php
// Include your database connection file here (e.g., db_connection.php)
include 'db_connection.php'; 

// Get the search query and course/instructor details from the GET request
$search_query = isset($_GET['search_query']) ? '%' . $_GET['search_query'] . '%' : '%';
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$instructor_id = isset($_GET['instructor_id']) ? $_GET['instructor_id'] : '';

// Fetch assessments based on the search query
$query = "SELECT assessment_title, assessment_description, created_at FROM assessments WHERE course_id = ? AND instructor_id = ? AND assessment_title LIKE ?";
$stmt = $pdo->prepare($query);
$stmt->execute([$course_id, $instructor_id, $search_query]);
$assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there are assessments and output the results as a table
if (!empty($assessments)): ?>
    <table class="assessment-table">
        <thead>
            <tr>
                <th>Title of Assessments</th>
                <th>Assessment Descriptions</th>
                <th>Sent On</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($assessments as $assessment): ?>
                <tr>
                    <td><?php echo htmlspecialchars($assessment['assessment_title']); ?></td>
                    <td><?php echo nl2br(htmlspecialchars($assessment['assessment_description'])); ?></td>
                    <td><?php echo date('F d, Y', strtotime($assessment['created_at'])); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php else: ?>
    <p>No assessments have been sent for this course yet.</p>
<?php endif; ?>
