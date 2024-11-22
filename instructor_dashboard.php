<?php
// Include database connection file
require 'db_connection.php';
session_start(); // Start a session

// Check if instructor is logged in
if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php"); // Redirect to login if not logged in
    exit();
}

$instructor_id = $_SESSION['instructor_id'];

// Fetch courses assigned to the instructor
$courses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$courses->execute([$instructor_id]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

// Fetch students enrolled in each course
$students_by_course = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $students = $pdo->prepare("SELECT s.id, s.name FROM students s 
                                JOIN enrollments e ON s.id = e.student_id 
                                WHERE e.course_id = ?");
    $students->execute([$course_id]);
    $students_by_course[$course_id] = $students->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch modules or content related to each course
$modules_by_course = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
    $modules->execute([$course_id]);
    $modules_by_course[$course_id] = $modules->fetchAll(PDO::FETCH_ASSOC);
}

// Handle sending assessment
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_assessment'])) {
    $course_id = $_POST['course_id'];
    $assessment_title = htmlspecialchars($_POST['assessment_title']);
    $assessment_description = htmlspecialchars($_POST['assessment_description']);

    // Save the assessment to the database
    $insert_assessment = $pdo->prepare("INSERT INTO assessments (course_id, instructor_id, assessment_title, assessment_description, created_at) 
                                         VALUES (?, ?, ?, ?, NOW())");
    $insert_assessment->execute([$course_id, $instructor_id, $assessment_title, $assessment_description]);

    echo "<script>alert('Assessment sent successfully for course ID: $course_id');</script>";
}

// Handle feedback submission for assessments
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
    $submission_id = $_POST['submission_id'];
    $feedback_text = htmlspecialchars($_POST['feedback_text']);

    // Save feedback to the database
    $insert_feedback = $pdo->prepare("INSERT INTO assessment_feedback (submission_id, user_id, user_type, comment, created_at) 
                                       VALUES (?, ?, 'instructor', ?, NOW())");
    $insert_feedback->execute([$submission_id, $instructor_id, $feedback_text]);

    echo "<script>alert('Feedback submitted successfully for submission ID: $submission_id');</script>";
}

// Fetch feedback for each assessment submission
$feedback_by_submission = [];
foreach ($courses as $course) {
    $course_id = $course['id'];
    $submissions = $pdo->prepare("SELECT asmt.id AS submission_id, asmt.student_id, asmt.submission_text, s.name AS student_name 
                                   FROM assessment_submissions asmt 
                                   JOIN students s ON asmt.student_id = s.id 
                                   WHERE asmt.assessment_id IN (SELECT id FROM assessments WHERE course_id = ?)");
    $submissions->execute([$course_id]);
    $feedback_by_submission[$course_id] = $submissions->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Instructor Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f3f3f3;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .course {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .course h3 {
            margin: 0 0 10px;
            color: #333;
        }
        .course p {
            color: #666;
        }
        .students, .modules, .assessment-form, .feedback-form {
            margin-top: 10px;
            padding: 10px;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .students h4, .modules h4, .assessment-form h4, .feedback-form h4 {
            margin: 0;
        }
        .student, .module {
            padding: 5px 0;
        }
        .assessment-form input, .assessment-form textarea, .feedback-form input, .feedback-form textarea {
            display: block;
            margin: 5px 0;
            width: 100%;
            padding: 8px;
        }
        .submission {
            margin-top: 10px;
            padding: 10px;
            background-color: #e9ecef;
            border: 1px solid #ced4da;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['instructor_name']); ?></h2>
        
        <h2>Your Courses</h2>
        <?php if (count($courses) > 0): ?>
            <?php foreach ($courses as $course): ?>
                <div class="course">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p><?php echo htmlspecialchars($course['course_description']); ?></p>

                    <!-- Display enrolled students -->
                    <div class="students">
                        <h4>Enrolled Students:</h4>
                        <?php
                        if (!empty($students_by_course[$course['id']])) {
                            foreach ($students_by_course[$course['id']] as $student): ?>
                                <div class="student"><?php echo htmlspecialchars($student['name']); ?></div>
                            <?php endforeach; ?>
                        <?php } else { ?>
                            <div class='student'>No students enrolled in this course.</div>
                        <?php } ?>
                    </div>

                    <!-- Display course modules -->
                    <div class="modules">
                        <h4>Course Content:</h4>
                        <?php
                        if (!empty($modules_by_course[$course['id']])) {
                            foreach ($modules_by_course[$course['id']] as $module): ?>
                                <div class="module"><?php echo htmlspecialchars($module['title']); ?></div>
                            <?php endforeach; ?>
                        <?php } else { ?>
                            <div class='module'>No content available for this course.</div>
                        <?php } ?>
                    </div>

                    <!-- Assessment Sending Form -->
                    <div class="assessment-form">
                        <h4>Send Assessment:</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                            <label for="assessment_title">Assessment Title:</label>
                            <input type="text" id="assessment_title" name="assessment_title" required>
                            <label for="assessment_description">Assessment Description:</label>
                            <textarea id="assessment_description" name="assessment_description" rows="4" required></textarea>
                            <input type="submit" name="send_assessment" value="Send Assessment">
                        </form>
                    </div>

                    <!-- Display Assessment Submissions -->
                    <div class="submissions">
                        <h4>Assessment Submissions:</h4>
                        <?php
                        if (!empty($feedback_by_submission[$course['id']])) {
                            foreach ($feedback_by_submission[$course['id']] as $submission): ?>
                                <div class="submission">
                                    <h5>Submission by: <?php echo htmlspecialchars($submission['student_name']); ?></h5>
                                    <p><?php echo htmlspecialchars($submission['submission_text']); ?></p>
                                    <form method="POST" action="">
                                        <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['submission_id']); ?>">
                                        <label for="feedback_text_<?php echo $submission['submission_id']; ?>">Feedback:</label>
                                        <textarea id="feedback_text_<?php echo $submission['submission_id']; ?>" name="feedback_text" rows="3" required></textarea>
                                        <input type="submit" name="submit_feedback" value="Submit Feedback">
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        <?php } else { ?>
                            <div class='submission'>No submissions available for this assessment.</div>
                        <?php } ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No courses available.</p>
        <?php endif; ?>
    </div>



    
</body>
</html>
