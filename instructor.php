<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['instructor_id'])) {
    header("Location: instructor_login.php");
    exit();
}
$instructor_id = $_SESSION['instructor_id'];
$instructor = $pdo->prepare("SELECT * FROM instructors WHERE id = ?");
$instructor->execute([$instructor_id]);
$instructor = $instructor->fetch(PDO::FETCH_ASSOC);
$courses = $pdo->prepare("SELECT * FROM courses WHERE instructor_id = ?");
$courses->execute([$instructor_id]);
$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

if (count($courses) > 0) {
    $students_by_course = [];
    foreach ($courses as $course) {
        $course_id = $course['id'];
        $students = $pdo->prepare("SELECT s.id, s.name, s.gender FROM students s 
        JOIN enrollments e ON s.id = e.student_id 
        WHERE e.course_id = ?");
        $students->execute([$course_id]);
        $students_by_course[$course_id] = $students->fetchAll(PDO::FETCH_ASSOC);
    }

    $modules_by_course = [];
    foreach ($courses as $course) {
        $course_id = $course['id'];
        $modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
        $modules->execute([$course_id]);
        $modules_by_course[$course_id] = $modules->fetchAll(PDO::FETCH_ASSOC);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['send_assessment'])) {
        $course_id = $_POST['course_id'];
        $assessment_title = htmlspecialchars($_POST['assessment_title']);
        $assessment_description = htmlspecialchars($_POST['assessment_description']);
        $insert_assessment = $pdo->prepare("INSERT INTO assessments (course_id, instructor_id, assessment_title, assessment_description, created_at) 
                                             VALUES (?, ?, ?, ?, NOW())");
        $insert_assessment->execute([$course_id, $instructor_id, $assessment_title, $assessment_description]);
        $_SESSION['successMessage'] = "Assessment sent successfully!";
        header("Location: " . $_SERVER['PHP_SELF'] . "?course_id=" . urlencode($course_id));
        exit;
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit_feedback'])) {
        $submission_id = $_POST['submission_id'];
        $feedback_text = htmlspecialchars($_POST['feedback_text']);
        $insert_feedback = $pdo->prepare("INSERT INTO assessment_feedback (submission_id, user_id, user_type, comment, created_at) 
                                         VALUES (?, ?, 'instructor', ?, NOW())");
        $insert_feedback->execute([$submission_id, $instructor_id, $feedback_text]);

        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

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
}

if (isset($_SESSION['successMessage'])) {
    echo '<p class="success">' . $_SESSION['successMessage'] . '</p>';
    unset($_SESSION['successMessage']);
}

if (isset($_SESSION['errorMessage'])) {
    echo '<p class="error">' . $_SESSION['errorMessage'] . '</p>';
    unset($_SESSION['errorMessage']);
}

function getStudentProgress($student_id, $course_id, $pdo)
{
    try {
        $stmt = $pdo->prepare("
            SELECT
                m.id AS module_id,
                CASE WHEN cm.module_id IS NOT NULL THEN 1 ELSE 0 END AS is_completed
            FROM
                modules m
            LEFT JOIN completed_modules cm 
                ON m.id = cm.module_id AND cm.student_id = :student_id
            WHERE
                m.course_id = :course_id
        ");
        $stmt->execute([
            'student_id' => $student_id,
            'course_id' => $course_id
        ]);
        $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $totalModules = count($modules);
        $completedCount = array_reduce($modules, function ($count, $module) {
            return $count + ($module['is_completed'] == 1 ? 1 : 0);
        }, 0);

        return ($totalModules > 0) ? round(($completedCount / $totalModules) * 100, 2) : 0;
    } catch (PDOException $e) {
        error_log("Database Error: " . $e->getMessage());
        return 0;
    }
}

if (isset($_POST['submit_post_comment'])) {
    $assessment_id = $_POST['assessment_id'];
    $comment = trim($_POST['comment']);
    $student_id = $_POST['student_id'];

    if (!empty($comment)) {
        $stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, content, created_at) 
                               VALUES (:post_id, :student_id, :content, NOW())");

        $stmt->execute([
            ':post_id' => $assessment_id,
            ':student_id' => $student_id,
            ':content' => $comment
        ]);

        header('Location: instructor.php');
        exit();
    } else {
        $error_message = "Comment cannot be empty.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instructor - <?php echo htmlspecialchars($_SESSION['instructor_name']); ?></title>
    <link rel="stylesheet" type="text/css" href="instructor.css">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.6.1/dist/sweetalert2.all.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .assessment-content .comment-widget {
            padding: 10px;
            border: 1px solid #000000;
        }

        #progress {
            flex-direction: column;
        }

        #progress h3 {
            text-align: center;
        }

        .widget-container {
            margin-top: 20px;
            display: flex;
            gap: 20px;
            flex-direction: column;
        }

        .modal-content {
            position: relative;
        }

        .assessment-container {
            padding: 20px;
            margin-bottom: 20px;
            border: 1px solid #000000;
        }

        .add-option {
            padding: 2px;
            border: 1px solid #000000;
        }
        .widget {
            padding: 20px;
            border: 1px solid #000000;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .widget-details {
            display: flex;
            gap: 10px;
        }

        .widget-details img {
            width: 100px;
            height: 100px;
            object-fit: cover;
        }

        .modal-close * {
            color: #000000;
        }

        .modal-close i {
            font-size: 25px;
        }

        .close-container {
            margin-bottom: 20px;
        }

        .close-container a {
            display: flex;
            align-items: center;
        }
    </style>
</head>

<body>
    <section id="header">
        <div class="wrapper">
            <div class="header-container">
                <img src="./images/logo.png" alt="Logo">
                <div id="profileModal" class="modal">
                    <div class="modal-content">
                        <span class="close-btn">&times;</span>
                        <h2>Instructor Profile</h2>
                        <p><strong>Name:</strong> <span id="modalName"><?php echo htmlspecialchars($instructor['name']); ?></span></p>
                        <p><strong>Gender:</strong> <span id="modalGender"><?php echo htmlspecialchars($instructor['gender']); ?></span></p>
                        <form action="update_profile.php" method="POST" enctype="multipart/form-data">
                            <div class="field-container">
                                <label for="profile_picture">Update Profile Picture:</label>
                                <input type="file" name="profile_picture" id="profile_picture">
                            </div>
                            <div class="btn-container">
                                <button type="submit" name="update_picture" value="">Update Profile Picture</button>
                            </div>
                        </form>
                        <form action="update_password_instructors.php" method="POST">
                            <div class="field-container">
                                <label for="new_password">New Password:</label>
                                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                            </div>
                            <div class="field-container">
                                <label for="confirm_password">Confirm Password:</label>
                                <input type="password" name="confirm_password" id="confirm_password" placeholder="Enter confirm password" required>
                            </div>
                            <div class="btn-container">
                                <button type="submit" name="update_password" value="">Update Password</button>
                            </div>
                        </form>
                    </div>
                </div>
                <nav>
                    <ul>
                        <li class="profile" id="profileBtn">
                            <img src="<?php echo !empty($instructor['profile_picture']) ? $instructor['profile_picture'] : './images/instructor.png'; ?>" alt="Profile">
                            <span><?php echo htmlspecialchars($_SESSION['instructor_name']); ?></span>
                        </li>

                        <div id="profileModal" class="modal">
                            <div class="modal-content">
                                <span class="close-btn">&times;</span>
                                <h2>Instructor Profile</h2>
                                <p><strong>Name:</strong> <span id="modalName"><?php echo htmlspecialchars($instructor['name']); ?></span></p>
                                <p><strong>Gender:</strong> <span id="modalGender"><?php echo htmlspecialchars($instructor['gender']); ?></span></p>
                            </div>
                        </div>
                        <!-- <li><a href="javascript:void(0);" id="logout-link" class="btn-secondary">Logout</a></li> -->
                    </ul>
                </nav>
                <script>
                    var modal = document.getElementById('profileModal');
                    var profileBtn = document.getElementById('profileBtn');
                    var closeBtn = document.querySelector('.close-btn');

                    profileBtn.addEventListener('click', function() {
                        modal.style.display = 'block';
                    });

                    closeBtn.addEventListener('click', function() {
                        modal.style.display = 'none';
                    });

                    window.addEventListener('click', function(event) {
                        if (event.target === modal) {
                            modal.style.display = 'none';
                        }
                    });
                </script>
            </div>
        </div>
    </section>

    <style>
        #sidebar #logout-link,
        #sidebar li:first-of-type a {
            display: flex;
            justify-content: center;
            text-decoration: none;
        }

        #sidebar li:first-of-type a::before {
            content: "\f075";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        #logout-link::before {
            content: "\f2f5";
            font-family: "Font Awesome 5 Free";
            font-weight: 900;
            font-size: 18px;
            transition: transform 0.3s ease;
        }
    </style>
    <section id="sidebar">
        <div class="sidebar-btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-list" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5m0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5" />
            </svg>
        </div>
        <ul>
            <li>
                <a href="forum.php" class="btn-secondary"><span>Forum</span></a>
            </li>
            <li>
                <a href="javascript:void(0);" id="logout-link" class="btn-secondary"><span>Logout</span></a>
            </li>
        </ul>
    </section>

    <script>
        $(document).ready(() => {
            document.getElementById('logout-link').addEventListener('click', function(e) {
                e.preventDefault();
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
                        window.location.href = 'index.php';
                    }
                });
            });

            let sidebarLocked = false;

            $('#sidebar').hover(
                function() {
                    if (!sidebarLocked) {
                        $(this).addClass('active');
                    }
                },
                function() {
                    if (!sidebarLocked) {
                        $(this).removeClass('active');
                    }
                }
            );

            $('#sidebar .sidebar-btn').click(function() {
                if (sidebarLocked) {
                    sidebarLocked = false;
                    $('#sidebar').removeClass('active');
                } else {
                    sidebarLocked = true;
                    $('#sidebar').addClass('active');
                }
            });
        });
    </script>
    <section id="instructor">
        <div class="wrapper">
            <div class="instructor-container content">
                <section class="profile-section">
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
                <section class="tabs">
                    <button class="active" onclick="showTabContent('assigned-course', event)">ASSIGNED COURSE</button>
                    <button onclick="showTabContent('progress', event)">MY LEARNERS</button>
                    <button onclick="showTabContent('evaluate', event)">EVALUATE</button>
                </section>
                <section id="assigned-course" class="tab-content">
                    <?php if (count($courses) > 0): ?>
                        <?php foreach ($courses as $course): ?>
                            <div class="course">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <p class="course-description"><?php echo htmlspecialchars($course['course_description']); ?></p>
                                <button type="button" class="btn-primary" data-bs-toggle="modal" data-bs-target="#quizModal" data-id="<?php echo $course['id'] ?>">
                                    View Quiz
                                </button>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>No courses available.</p>
                    <?php endif; ?>
                </section>
                <div class="modal fade" id="quizModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p>Loading...</p>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn-primary save-quiz" style="display: none;">Save changes</button>
                            </div>
                        </div>
                    </div>
                </div>

                <script>
                    $(document).ready(() => {
                        $('[data-bs-target="#quizModal"]').on('click', function() {
                            const courseId = $(this).data('id'),
                                modalBody = $('#quizModal .modal-body'),
                                saveQuizButton = $('.save-quiz');
                            modalBody.html('<p>Loading...</p>');
                            saveQuizButton.hide();

                            $.ajax({
                                url: 'get_quiz.php',
                                method: 'GET',
                                dataType: 'json',
                                data: {
                                    course_id: courseId
                                },
                                success(response) {
                                    if (response.error) {
                                        modalBody.html(`
                                            <form id="quizForm">
                                                <h4>Create Quiz</h4>
                                                <label for="quizTitle">Quiz Title</label>
                                                <input type="text" id="quizTitle" name="quiz_title" placeholder="Enter quiz title" required>
                                                <div id="questionsContainer">
                                                    <div class="question">
                                                        <input type="text" name="questions[0][question]" placeholder="Enter question" required>
                                                        <div class="options">
                                                            <input type="text" name="questions[0][options][0][option_text]" placeholder="Option 1" required>
                                                            <input type="text" name="questions[0][options][1][option_text]" placeholder="Option 2" required>
                                                        </div>
                                                        <label for="correctAnswer0">Select Correct Answer</label>
                                                        <select name="questions[0][correct_answer]" id="correctAnswer0" required>
                                                            <option value="0">Option 1</option>
                                                            <option value="1">Option 2</option>
                                                        </select>
                                                        <button type="button" class="add-option">Add Option</button>
                                                    </div>
                                                </div>
                                                <button type="button" id="addQuestion">Add Question</button>
                                            </form>
                                        `);
                                        saveQuizButton.text('Create Quiz').show();
                                    } else {
                                        let quizHtml = `
                                            <form id="quizForm">
                                                <h4>Edit Quiz</h4>
                                                <label for="quizTitle">Quiz Title</label>
                                                <input type="text" id="quizTitle" name="quiz_title" value="${response.quiz_title}" required>
                                                <div id="questionsContainer">
                                        `;
                                        response.questions.forEach((question, index) => {
                                            quizHtml += `
                                                <div class="question">
                                                    <input type="hidden" name="questions[${index}][question_id]" value="${question.question_id}">
                                                    <input type="text" name="questions[${index}][question]" value="${question.question}" required>
                                                    <div class="options">
                                            `;
                                            question.options.forEach((option, optIndex) => {
                                                quizHtml += `
                                                    <input type="hidden" name="questions[${index}][options][${optIndex}][option_id]" value="${option.option_id || ''}">
                                                    <input type="text" name="questions[${index}][options][${optIndex}][option_text]" value="${option.option_text}" required>
                                                `;
                                            });
                                            quizHtml += `
                                                    </div>
                                                    <label for="correctAnswer${index}">Select Correct Answer</label>
                                                    <select name="questions[${index}][correct_answer]" id="correctAnswer${index}" required>
                                            `;
                                            question.options.forEach((option, optIndex) => {
                                                quizHtml += `
                                                    <option value="${optIndex}" ${optIndex == question.correct_answer ? 'selected' : ''}>Option ${optIndex + 1}</option>
                                                `;
                                            });
                                            quizHtml += `
                                                    </select>
                                                    <button type="button" class="add-option">Add Option</button>
                                                </div>
                                            `;
                                        });
                                        quizHtml += '<button type="button" id="addQuestion">Add Question</button></form>';
                                        modalBody.html(quizHtml);
                                        saveQuizButton.text('Save Changes').show();
                                    }
                                },
                                error() {
                                    modalBody.html('<p>Failed to load quiz. Please try again later.</p>');
                                }
                            });
                        });

                        $(document).on('click', '#addQuestion', function() {
                            const questionIndex = $('#questionsContainer .question').length;
                            const newQuestionHtml = `
                                <div class="question">
                                    <input type="text" name="questions[${questionIndex}][question]" placeholder="Enter question" required>
                                    <div class="options">
                                        <input type="text" name="questions[${questionIndex}][options][0][option_text]" placeholder="Option 1" required>
                                        <input type="text" name="questions[${questionIndex}][options][1][option_text]" placeholder="Option 2" required>
                                    </div>
                                    <label for="correctAnswer${questionIndex}">Select Correct Answer</label>
                                    <select name="questions[${questionIndex}][correct_answer]" id="correctAnswer${questionIndex}" required>
                                        <option value="0">Option 1</option>
                                        <option value="1">Option 2</option>
                                    </select>
                                    <button type="button" class="add-option">Add Option</button>
                                </div>
                            `;
                            $('#questionsContainer').append(newQuestionHtml);
                        });

                        $(document).on('click', '.add-option', function() {
                            const optionsContainer = $(this).siblings('.options'),
                                questionIndex = $(this).closest('.question').index(),
                                optionIndex = optionsContainer.children('input[type="text"]').length;

                            optionsContainer.append(`
                                <input type="text" name="questions[${questionIndex}][options][${optionIndex}][option_text]" placeholder="Option ${optionIndex + 1}" required>
                            `);

                            const select = $(this).siblings('label').next();
                            select.append(`<option value="${optionIndex}">Option ${optionIndex + 1}</option>`);
                        });

                        // Save the quiz data
                        $('.save-quiz').on('click', () => {
                            const formData = $('#quizForm').serializeArray();
                            const courseId = $('[data-bs-target="#quizModal"]').data('id');
                            formData.push({
                                name: 'course_id',
                                value: courseId
                            });

                            // Validate form before submitting
                            let valid = true;
                            $('#quizForm input, #quizForm select').each(function() {
                                if ($(this).val() === "") {
                                    valid = false;
                                    alert("Please fill out all fields before saving.");
                                    return false;
                                }
                            });

                            if (valid) {
                                $.ajax({
                                    url: 'save_quiz.php',
                                    method: 'POST',
                                    data: $.param(formData),
                                    dataType: 'json',
                                    success(response) {
                                        if (response.success) {
                                            alert("Quiz successfully updated.");
                                            $('#quizModal').modal('hide');
                                        } else {
                                            alert("Quiz update failed.");
                                        }
                                    },
                                    error(xhr, status, error) {
                                        console.log(`Error: ${xhr.responseText || error}`);
                                    },
                                });
                            }
                        });
                    });
                </script>

                <section id="progress" class="tab-content hidden">
                    <?php
                    foreach ($courses as $course):
                        $students_by_course = $pdo->prepare("SELECT * FROM enrollments JOIN students ON students.id = enrollments.student_id JOIN courses ON courses.id = enrollments.course_id WHERE course_id = ?");
                        $students_by_course->execute([$course['id']]);
                        $students = $students_by_course->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($students)): ?>
                            <h3><?php echo htmlspecialchars($course['course_name']); ?> </h3>
                            <table style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th>Profile Picture</th>
                                        <th>Name</th>
                                        <th>Progress</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students as $student): ?>
                                        <tr class="student-item" data-name="<?php echo strtolower($student['name']); ?>">
                                            <td>
                                                <img src="<?php echo !empty($student['profile_pic']) ? './uploads/profile_picture/' . $student['profile_pic'] : './uploads/profile_picturedefault_profile.jpg'; ?>" alt="Student Profile" class="student-img">
                                            </td>
                                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                                            <td>
                                                <?php

                                                $progress = getStudentProgress($student['student_id'], $course['id'], $pdo);
                                                ?>
                                                <div class="progress-container">
                                                    <div class="progress-bar" style="width: <?php echo $progress; ?>%"></div>
                                                </div>
                                                <span class="progress-text"><?php echo $progress; ?>%</span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No students enrolled in <?php echo htmlspecialchars($course['course_name']); ?>.</p>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    </tbody>
                </section>
                <section id="evaluate" class="tab-content hidden">
                    <div class="assessment-form" id="assessment-form" style="display: none;">
                        <h4>Send Assessment:</h4>
                        <form method="POST" action="">
                            <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course['id']); ?>">
                            <label for="assessment_title">Assessment Title:</label>
                            <input type="text" id="assessment_title" name="assessment_title" required>

                            <label for="assessment_description">Assessment Description:</label>
                            <textarea id="assessment_description" name="assessment_description" rows="4" required></textarea>

                            <input type="submit" name="send_assessment" class="btn-primary" value="Send Assessment">
                        </form>
                    </div>
                    <button id="toggle-assessment-form-btn" onclick="toggleAssessmentForm()" class="btn-primary">Send Assessment</button>
                    <button type="button" class="btn-primary view-responses-btn" data-bs-toggle="modal" data-bs-target="#viewModal" data-id="<?php echo $course['id']; ?>" style="margin-top:10px;">
                        View Responses
                    </button>
                    <div class="widget-container">
                        <?php
                        try {
                            $stmt = $pdo->prepare("
                                SELECT
                                    assessment_submissions.id,
                                    assessment_submissions.assessment_id,
                                    assessment_submissions.student_id,
                                    assessment_submissions.course_id,
                                    assessment_submissions.submission_text,
                                    assessment_submissions.created_at,
                                    students.name AS student_name,
                                    comments.*,
                                    qr.total,
                                    qr.score
                                FROM
                                    assessment_submissions
                                JOIN students ON students.id = assessment_submissions.student_id
                                JOIN courses ON courses.id = assessment_submissions.course_id
                                LEFT JOIN comments ON comments.post_id = assessment_submissions.id
                                LEFT JOIN quiz_results qr ON qr.student_id = students.id
                                WHERE courses.instructor_id = :instructor_id
                                ORDER BY
                                    assessment_submissions.created_at DESC
                            ");
                            $stmt->bindValue(':instructor_id', $_SESSION['instructor_id'], PDO::PARAM_INT);
                            $stmt->execute();
                            $assessments = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        } catch (PDOException $e) {
                            echo "Error: " . $e->getMessage();
                        }
                        ?>
                        <?php if (!empty($assessments)): ?>
                            <?php foreach ($assessments as $assessment): ?>
                                <div class="widget">
                                    <div class="widget-details">
                                        <img src="./images/profile.png" alt="Assessment Image">
                                        <div class="personal-details">
                                            <h4><?php echo htmlspecialchars($assessment['student_name']); ?></h4>
                                            <?php
                                            if (!empty($assessment['submission_text'])) {
                                                echo "<p>Passed his assessment work.</p>";
                                            } else {
                                                echo "<p>Does not pass his assessment work.</p>";
                                            }
                                            ?>
                                            <p><?php echo htmlspecialchars($assessment['created_at']); ?></p>
                                        </div>
                                    </div>
                                    <div class="feedback-container">
                                        <a href="#" class="evaluate-btn"
                                            data-student-name="<?php echo htmlspecialchars($assessment['student_name']); ?>"
                                            data-submission-text="<?php echo htmlspecialchars($assessment['submission_text']); ?>"
                                            data-created-at="<?php echo htmlspecialchars($assessment['created_at']); ?>"
                                            data-submission-id="<?php echo htmlspecialchars($assessment['id']); ?>"
                                            data-assessment-link="./uploads/<?php echo $assessment['submission_text']; ?>">
                                            <h4>Send Feedback</h4>
                                        </a>
                                        <a href="#" class="comment-btn">
                                            <h4>View Comment</h4>
                                        </a>
                                        <p>Score: <?php echo $assessment['score']; ?> / <?php echo $assessment['total']; ?></p>
                                    </div>

                                </div>
                                <div class="modal fade" id="viewModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="exampleModalLabel">Quiz Results</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body" id="quizResultsContainer">
                                                <p>Loading...</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <script>
                                    const viewModal = document.getElementById('viewModal');
                                    const resultsContainer = document.getElementById('quizResultsContainer');

                                    document.querySelectorAll('.view-responses-btn').forEach(button => {
                                        button.addEventListener('click', function() {
                                            const courseId = this.getAttribute('data-id');

                                            resultsContainer.innerHTML = '<p>Loading...</p>';

                                            fetch(`fetch_quiz_results.php?course_id=${courseId}`)
                                                .then(response => {
                                                    if (!response.ok) {
                                                        throw new Error(`HTTP error! Status: ${response.status}`);
                                                    }
                                                    return response.text();
                                                })
                                                .then(text => {
                                                    try {
                                                        const data = JSON.parse(text);
                                                        if (data.error) {
                                                            resultsContainer.innerHTML = `<p>${data.error}</p>`;
                                                        } else {
                                                            let tableHtml = `
                                                                <table class="table table-striped">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>Student Name</th>
                                                                            <th>Quiz Title</th>
                                                                            <th>Score</th>
                                                                            <th>Total</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                            `;

                                                            data.forEach(result => {
                                                                tableHtml += `
                                                                    <tr>
                                                                        <td>${result.student_name}</td>
                                                                        <td>${result.quiz_title}</td>
                                                                        <td>${result.score}</td>
                                                                        <td>${result.total}</td>
                                                                    </tr>
                                                                `;
                                                            });

                                                            tableHtml += `
                                                                    </tbody>
                                                                </table>
                                                            `;
                                                            resultsContainer.innerHTML = tableHtml;
                                                        }
                                                    } catch (error) {
                                                        resultsContainer.innerHTML = `<p>Invalid JSON response: ${text}</p>`;
                                                    }
                                                })
                                                .catch(error => {
                                                    resultsContainer.innerHTML = `<p>Error fetching data: ${error.message}</p>`;
                                                });

                                        });
                                    });
                                </script>
                                <div id="commendModal" class="modal">
                                    <div class="modal-content">
                                        <div class="close-container">
                                            <a href="#" class="modal-close">
                                                <i class="bi bi-chevron-left"></i>
                                                <span>Back</span>
                                            </a>
                                        </div>

                                        <h4>Assessment Content</h4>
                                        <?php
                                        if (!empty($assessments)) {
                                            foreach ($assessments as $assessment) {
                                        ?>
                                                <div class="assessment-content">
                                                    <div class="comment-widget">
                                                        <?php if (!empty($assessment['content'])): ?>
                                                            <p><?php echo nl2br(htmlspecialchars($assessment['content'])); ?></p>
                                                            <span><?php echo date('F d, Y', strtotime($assessment['created_at'])); ?></span>
                                                        <?php else: ?>
                                                            <p>No content available for this assessment.</p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                        <?php
                                            }
                                        } else {
                                            echo "<p>No assessments available for this course.</p>";
                                        }
                                        ?>

                                        <p><strong>Add a Comment</strong></p>
                                        <form action="" method="POST">
                                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($assessment['student_id']); ?>">
                                            <input type="hidden" name="assessment_id" id="assessment_id" value="<?php echo htmlspecialchars($assessment['id']); ?>">
                                            <textarea name="comment" id="comment-text" cols="30" rows="10" required></textarea>
                                            <button type="submit" name="submit_post_comment" class="btn-primary" style="float: right; background: #2563eb; color: #fff; border: none; padding: 10px 15px; border-radius: 5px; cursor: pointer;">
                                                <i class="bi bi-send-fill"></i>
                                                <span>Comment</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                                <div id="assessmentModal" class="modal">
                                    <div class="modal-content">
                                        <div class="close-container">
                                            <a href="#" class="modal-close">
                                                <i class="bi bi-chevron-left"></i>
                                                <span>Back</span>
                                            </a>
                                        </div>
                                        <h4 id="student_name"></h4>
                                        <div class="assessment-container">
                                            <p><a id="assessment-link" href="" target="_blank">View the Assessment</a></p>
                                        </div>
                                        <p><strong>Add comment</strong></p>
                                        <form action="submit_comment.php" method="POST">
                                            <input type="text" name="assessment_id" id="assessment_id" hidden>
                                            <textarea name="comment" id="comment-text" cols="30" rows="10"></textarea>
                                            <button type="submit" name="submit_comment" class="btn-primary" style="float: right;background: #2563eb;">
                                                <i class="bi bi-send-fill"></i>
                                                <span>Send</span>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No assessments available.</p>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </div>
    </section>

    <style>
        .progress-container {
            width: 100%;
            height: 20px;
            background-color: #f3f3f3;
            border-radius: 5px;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background-color: #4caf50;
        }

        .progress-text {
            display: block;
            text-align: center;
            margin-top: 5px;
            font-weight: bold;
        }
    </style>
    <script>
        function searchStudents() {
            let input = document.getElementById('studentSearch');
            let filter = input.value.toLowerCase();
            let students = document.querySelectorAll('.student-item');

            students.forEach(function(student) {
                let name = student.getAttribute('data-name');
                if (name.indexOf(filter) > -1) {
                    student.style.display = "";
                } else {
                    student.style.display = "none";
                }
            });
        }


        function showTabContent(tabId, event) {
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            const buttons = document.querySelectorAll('.tabs button');
            buttons.forEach(button => button.classList.remove('active'));
            document.getElementById(tabId).classList.remove('hidden');
            event.target.classList.add('active');
        }

        function toggleAssessmentForm() {
            const form = document.getElementById("assessment-form");
            if (form.style.display === "none" || form.style.display === "") {
                form.style.display = "block";
            } else {
                form.style.display = "none";
            }
        }

        function openModal() {
            document.getElementById("sent-assessments-modal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("sent-assessments-modal").style.display = "none";
        }

        window.onclick = function(event) {
            const modal = document.getElementById("sent-assessments-modal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        const close = document.querySelectorAll('.modal-close');

        close.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                document.getElementById("assessmentModal").style.display = "none";
                document.getElementById("commendModal").style.display = "none";
            });
        });



        const commendButton = document.querySelectorAll('.comment-btn');
        commendButton.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                document.getElementById("commendModal").style.display = "block";
            });
        });

        const evaluateButtons = document.querySelectorAll('.evaluate-btn');
        evaluateButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();

                const studentName = this.getAttribute('data-student-name');
                const submissionText = this.getAttribute('data-submission-text');
                const createdAt = this.getAttribute('data-created-at');
                const submissionId = this.getAttribute('data-submission-id');
                const assessmentLink = this.getAttribute('data-assessment-link');

                document.getElementById('student_name').textContent = studentName;
                document.getElementById('assessment-link').href = assessmentLink;
                document.getElementById('assessment-link').textContent = submissionText;
                document.getElementById("assessmentModal").style.display = "block";
                document.getElementById("assessment_id").value = submissionId;

            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>

</html>