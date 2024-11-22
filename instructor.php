<?php
session_start(); // Start a session
// Include database connection file
require 'db_connection.php';

// Check if instructor is logged in
if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php"); // Redirect to login if not logged in
    exit();
}
$instructor_id = $_SESSION['instructor_id'];

// Fetch the instructor's details from the database
$instructor = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
$instructor->execute([$instructor_id]);
$instructor = $instructor->fetch(PDO::FETCH_ASSOC);

// Fetch courses assigned to the instructor
$courses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$courses->execute([$instructor_id]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

// Ensure that $courses is not empty before proceeding with the loops
if (count($courses) > 0) {
    // Fetch students enrolled in each course
    $students_by_course = [];
    foreach ($courses as $course) {
        $course_id = $course['id'];
        $students = $pdo->prepare("SELECT s.id, s.name, s.gender FROM students s 
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

        // Set success message in session
        $_SESSION['successMessage'] = "Assessment sent successfully!";
        
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?course_id=" . urlencode($course_id));
        exit;
    }

    // Handle feedback submission for assessments
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
      $submission_id = $_POST['submission_id'];
      $feedback_text = htmlspecialchars($_POST['feedback_text']);

      // Save feedback to the database
      $insert_feedback = $pdo->prepare("INSERT INTO assessment_feedback (submission_id, user_id, user_type, comment, created_at) 
                                         VALUES (?, ?, 'instructor', ?, NOW())");
      $insert_feedback->execute([$submission_id, $instructor_id, $feedback_text]);

      // Redirect to the same page to prevent resubmission
      header("Location: " . $_SERVER['PHP_SELF']);
      exit(); // Ensure no further code is executed
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
} else {
    // Handle case when no courses are found for the instructor
    echo '<p>No courses assigned to this instructor.</p>';
}

if (isset($_SESSION['successMessage'])) {
    echo '<p class="success">' . $_SESSION['successMessage'] . '</p>';
    unset($_SESSION['successMessage']);
}

if (isset($_SESSION['errorMessage'])) {
    echo '<p class="error">' . $_SESSION['errorMessage'] . '</p>';
    unset($_SESSION['errorMessage']);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor - <?php echo htmlspecialchars($_SESSION['instructor_name']); ?></title>
    <link rel="stylesheet" type="text/css" href="instructor.css">
    <!-- Include SweetAlert2 CSS and JS -->

    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.all.min.js"></script>

</head>
<body>
<header>
<div class="header-container">
<div class="logo">
        <img src="./images/logo.png" alt="Logo">
    </div>
    <!-- Modal -->
    <div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Instructor Profile</h2>
        <p><strong>Name:</strong> <span id="modalName"><?php echo htmlspecialchars($instructor['name']); ?></span></p>
        <p><strong>Gender:</strong> <span id="modalGender"><?php echo htmlspecialchars($instructor['gender']); ?></span></p>

        <!-- Update Profile Picture Form -->
        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
            <label for="profile_picture">Update Profile Picture:</label>
            <input type="file" name="profile_picture" id="profile_picture">
            <input type="submit" name="update_picture" value="Update Profile Picture">
        </form>

        <!-- Update Password Form -->
        <form action="update_password_instructors.php" method="POST">
            <label for="new_password">New Password:</label>
            <input type="password" name="new_password" id="new_password" required>

            <label for="confirm_password">Confirm Password:</label>
            <input type="password" name="confirm_password" id="confirm_password" required>

            <input type="submit" name="update_password" value="Update Password">
        </form>
    </div>
</div>


    <nav>
        <ul>
           <!-- Profile button inside the navigation -->
<li class="profile" id="profileBtn">
<img src="<?php echo !empty($instructor['profile_picture']) ? $instructor['profile_picture'] : './images/instructor.png'; ?>" alt="Profile">
    <span><?php echo htmlspecialchars($_SESSION['instructor_name']); ?></span>
</li>

<!-- Modal for profile -->
<div id="profileModal" class="modal">
    <div class="modal-content">
        <span class="close-btn">&times;</span>
        <h2>Instructor Profile</h2>
        <p><strong>Name:</strong> <span id="modalName"><?php echo htmlspecialchars($instructor['name']); ?></span></p>
        <p><strong>Gender:</strong> <span id="modalGender"><?php echo htmlspecialchars($instructor['gender']); ?></span></p>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Get the modal and the profile button
    var modal = document.getElementById('profileModal');
    var profileBtn = document.getElementById('profileBtn');
    var closeBtn = document.querySelector('.close-btn'); // Close button inside the modal

    // Add click event listener to the profile button
    profileBtn.addEventListener('click', function() {
        // Display the modal when the profile button is clicked
        modal.style.display = 'block';
    });

    // Add click event listener to the close button to close the modal
    closeBtn.addEventListener('click', function() {
        modal.style.display = 'none';
    });

    // Close the modal if the user clicks outside the modal
    window.addEventListener('click', function(event) {
        if (event.target === modal) {
            modal.style.display = 'none';
        }
    });
});

</script>

            <!-- Logout link -->
            <li><a href="javascript:void(0);" id="logout-link">Logout</a></li>
        </ul>
    </nav>
</div>
</header>

<script>
// Ensure the DOM is fully loaded before executing the script
document.addEventListener('DOMContentLoaded', function() {
    // Add an event listener to the logout link
    document.getElementById('logout-link').addEventListener('click', function(e) {
        // Prevent the default link behavior (navigating to index.php)
        e.preventDefault();

        // Show SweetAlert confirmation
        Swal.fire({
            title: 'Are you sure?',
            text: 'Do you want to logout?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Logout',
            cancelButtonText: 'Cancel',
            reverseButtons: true
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to index.php or handle logout process
                window.location.href = 'index.php'; // You can replace this with your logout script
            }
        });
    });
});
</script>


<main>
<section class="profile-section">
    <!-- Dynamic Profile Picture -->
    <img src="<?php echo !empty($instructor['profile_picture']) ? $instructor['profile_picture'] : './images/instructor.png'; ?>" alt="Instructor Profile" class="profile-img">
    <div class="profile-info">
        <h2>Welcome, <?php echo htmlspecialchars($_SESSION['instructor_name']); ?></h2>
        <p>Instructor</p>
        <?php if (count($courses) > 0): ?>
            <p class="assigned-course">Assigned Course: <?php echo htmlspecialchars($courses[0]['course_name']); ?></p>
        <?php else: ?>
            <p class="assigned-course">Assigned Course: None</p>
        <?php endif; ?>
    </div>
</section>
</main>

    <section class="tabs">
        <button class="active" onclick="showTabContent('assigned-course', event)">ASSIGNED COURSE</button>
        <button onclick="showTabContent('my-learners', event)">MY LEARNERS</button>
        <button onclick="showTabContent('evaluate', event)">EVALUATE</button>
    </section>

    <div id="assigned-course" class="tab-content">
    <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
            <div class="course">
                <h3 class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                <p class="course-description"><?php echo htmlspecialchars($course['course_description']); ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>No courses available.</p>
    <?php endif; ?>
</div>

<style>
    #assigned-course {
        margin-top: 20px;
    }

    .course {
        margin-bottom: 20px; /* Space between courses */
        padding: 15px;
        border: 1px solid #ddd; /* Optional: Add a border to visually separate courses */
        border-radius: 5px;
        background-color: #f9f9f9; /* Light background for better readability */
    }

    .course-title {
        font-size: 1.5rem; /* Adjust font size for the title */
        font-weight: bold;
        margin-bottom: 10px; /* Space between the title and description */
        word-wrap: break-word; /* Ensure long words break into the next line */
        overflow-wrap: break-word; /* Support for older browsers */
        
    }

    .course-description {
        font-size: 1rem; /* Adjust font size for the description */
        line-height: 1.5; /* Ensure sufficient line spacing for readability */
        word-wrap: break-word; /* Ensure long words break into the next line */
        overflow-wrap: break-word; /* Support for older browsers */
    }
</style>


    <div id="my-learners" class="tab-content hidden">
    <h3>My Learners</h3>
    <?php if (count($courses) > 0): ?>
        <?php foreach ($courses as $course): ?>
            <div class="course">
                <h4>Course: <?php echo htmlspecialchars($course['course_name']); ?></h4>
                <div class="students">
                    <h5>Enrolled Students:</h5>
                    <?php
                    if (!empty($students_by_course[$course['id']])) {
                        foreach ($students_by_course[$course['id']] as $student): ?>
                            <div class="student">
                                <?php echo htmlspecialchars($student['name']); ?> 
                                (<?php echo htmlspecialchars($student['gender']); ?>)
                            </div>
                        <?php endforeach;
                    } else { ?>
                        <div class="student">No students enrolled in this course.</div>
                    <?php } ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div>No courses found.</div>
    <?php endif; ?>
</div>


<div id="evaluate" class="tab-content hidden">
    <h3>Evaluate</h3>

    <!-- Display success message if assessment was sent -->
    <?php if (isset($_SESSION['successMessage'])): ?>
        <div class="success-message" style="color: green;">
            <?php echo htmlspecialchars($_SESSION['successMessage']); ?>
        </div>
        <?php unset($_SESSION['successMessage']); ?>
        <script>
            // Trigger SweetAlert after success
            Swal.fire({
                icon: 'success',
                title: 'Assessment Sent!',
                text: 'Your assessment has been sent successfully.',
                showConfirmButton: true,
            });
        </script>
    <?php endif; ?>

<!-- Assessment form (initially hidden) -->
<div class="assessment-form" id="assessment-form" style="display: none;">
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

<!-- Button to toggle the assessment form visibility -->
<button id="toggle-assessment-form-btn" onclick="toggleAssessmentForm()">Send Assessment</button>

<!-- Show Sent Assessments Button -->
<!-- Show Sent Assessments Button -->
<button id="show-assessments-btn" onclick="openModal()">Show Sent Assessments</button>

<!-- Modal for displaying sent assessments -->
<div id="sent-assessments-modal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h4>Sent Assessments:</h4>
        <?php
        $fetch_assessments = $pdo->prepare("SELECT assessment_title, assessment_description, created_at FROM assessments WHERE course_id = ? AND instructor_id = ?");
        $fetch_assessments->execute([$course['id'], $instructor_id]);
        $assessments = $fetch_assessments->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($assessments)): ?>
            <ul>
                <?php foreach ($assessments as $assessment): ?>
                    <li class="assessment-item">
                        <strong class="assessment-title"><?php echo htmlspecialchars($assessment['assessment_title']); ?></strong><br>
                        <p class="assessment-description"><?php echo nl2br(htmlspecialchars($assessment['assessment_description'])); ?></p>
                        <small>Sent on: <?php echo date('F d, Y', strtotime($assessment['created_at'])); ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No assessments have been sent for this course yet.</p>
        <?php endif; ?>
    </div>
</div>

<!-- JavaScript to toggle the form visibility and modal functionality -->
<script>
    // Toggle visibility of the assessment form
    function toggleAssessmentForm() {
        const form = document.getElementById("assessment-form");
        if (form.style.display === "none" || form.style.display === "") {
            form.style.display = "block"; // Show form
        } else {
            form.style.display = "none"; // Hide form
        }
    }

    // Open modal for showing sent assessments
    function openModal() {
        document.getElementById("sent-assessments-modal").style.display = "block";
    }

    // Close modal
    function closeModal() {
        document.getElementById("sent-assessments-modal").style.display = "none";
    }

    // Close modal when clicking outside of it
    window.onclick = function(event) {
        const modal = document.getElementById("sent-assessments-modal");
        if (event.target === modal) {
            modal.style.display = "none";
        }
    }
</script>


<style>
    .submissions {
    margin: 20px;
}

.submission {
    margin-bottom: 20px;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 5px;
}

.submission h5 {
    margin: 5px 0;
    word-wrap: break-word; /* Ensure long words break and wrap */
    white-space: pre-wrap; /* Preserve whitespace and wrap text as needed */
    overflow-wrap: break-word; /* Allows the word to break */
}

.existing-feedback h5,
.student-comments h5,
.reply h5 {
    word-wrap: break-word;
    white-space: pre-wrap;
    overflow-wrap: break-word;
}

textarea {
    width: 100%; /* Ensure the textarea spans the full width of its container */
    box-sizing: border-box; /* Include padding and border in the element's total width */
}

.replies, .comment {
    padding: 10px;
    border-top: 1px solid #ccc;
    margin-top: 10px;
}

.replies {
    margin-top: 15px;
    padding-left: 20px;
}

</style>
    <div class="submissions">
        <h4>Assessment Submissions:</h4>
        <?php if (empty($course['id'])): ?>
            <p>No course assigned for you</p>
        <?php elseif (!empty($feedback_by_submission[$course['id']])): ?>
            <?php foreach ($feedback_by_submission[$course['id']] as $submission): ?>
                <div class="submission">
                    <h5>Submission by: <?php echo htmlspecialchars($submission['student_name']); ?></h5>
                    <h5>Submitted Assessment: <?php echo htmlspecialchars($submission['submission_text']); ?></h5>

                    <?php
                    $feedback_check = $pdo->prepare("SELECT * FROM assessment_feedback WHERE submission_id = ? AND user_id = ? AND user_type = 'instructor'");
                    $feedback_check->execute([$submission['submission_id'], $_SESSION['user_id']]);
                    $existing_feedback = $feedback_check->fetch(PDO::FETCH_ASSOC);

                    if ($existing_feedback): ?>
                        <div class="existing-feedback">
                            <strong>Your Feedback:</strong>
                            <h5><?php echo nl2br(htmlspecialchars($existing_feedback['comment'])); ?></h5>
                            <p>Submitted on: <?php echo date('F d, Y', strtotime($existing_feedback['created_at'])); ?></p>
                        </div>
                    <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="submission_id" value="<?php echo htmlspecialchars($submission['submission_id']); ?>">
                            <label for="feedback_text_<?php echo $submission['submission_id']; ?>">Feedback:</label>
                            <textarea id="feedback_text_<?php echo $submission['submission_id']; ?>" name="feedback_text" rows="3" required></textarea>
                            <input type="submit" name="submit_feedback" value="Submit Feedback">
                        </form>
                    <?php endif; ?>

                    <?php
                    $comments_query = $pdo->prepare("SELECT * FROM comments WHERE post_id = ?");
                    $comments_query->execute([$submission['submission_id']]);
                    $comments = $comments_query->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                    <?php if (!empty($comments)): ?>
                        <div class="student-comments">
                            <p style="color: green;">Student Comments</p>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment">
                                    <p><strong>Comment:</strong></p><h5><?php echo nl2br(htmlspecialchars($comment['content'])); ?></h5>
                                    <p>Posted on: <?php echo date('F d, Y', strtotime($comment['created_at'])); ?></p>

                                    <?php
                                    $replies_query = $pdo->prepare("SELECT * FROM replies WHERE comment_id = ?");
                                    $replies_query->execute([$comment['comment_id']]);
                                    $replies = $replies_query->fetchAll(PDO::FETCH_ASSOC);
                                    ?>

                                    <?php if (!empty($replies)): ?>
                                        <div class="replies">
                                            <p style="color: blue;">Your Replies</p>
                                            <?php foreach ($replies as $reply): ?>
                                                <div class="reply">
                                                    <p><strong>Reply:</strong></p>
                                                    <h5><?php echo nl2br(htmlspecialchars($reply['reply_content'])); ?></h5>
                                                    <p>Posted on: <?php echo date('F d, Y', strtotime($reply['created_at'])); ?></p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>

                                    <form method="POST" action="reply.php">
                                        <input type="hidden" name="comment_id" value="<?php echo htmlspecialchars($comment['comment_id']); ?>">
                                        <label for="reply_text_<?php echo htmlspecialchars($comment['comment_id']); ?>">Reply:</label>
                                        <textarea id="reply_text_<?php echo htmlspecialchars($comment['comment_id']); ?>" name="reply_text" rows="2" required></textarea>
                                        <input type="submit" name="submit_reply" value="Reply">
                                    </form>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No comments for this submission.</p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class='submission'>No submissions available for this assessment.</div>
        <?php endif; ?>
    </div>
</div>



<script>
    function showTabContent(tabId, event) {
        // Hide all tab content
        const contents = document.querySelectorAll('.tab-content');
        contents.forEach(content => content.classList.add('hidden'));

        // Remove active class from all buttons
        const buttons = document.querySelectorAll('.tabs button');
        buttons.forEach(button => button.classList.remove('active'));

        // Show the selected tab content and add active class to the button
        document.getElementById(tabId).classList.remove('hidden');
        event.target.classList.add('active');
    }
</script>
<style>
    .swal2-title{
        background-color: white;
    }
</style>
</body>
</html>