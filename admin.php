<?php
include 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];
    $instructor_id = $_POST['instructor_id'];
    $course_image = null;

    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['course_image']['tmp_name'];
        $name = basename($_FILES['course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ?, course_image = ?, instructor_id = ? WHERE id = ?");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id, $course_id])) {
        echo "<p>Course updated successfully!</p>";
    } else {
        echo "<p>Error updating course.</p>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $course_name = $_POST['new_course_name'];
    $course_description = $_POST['new_course_description'];
    $instructor_id = $_POST['instructor_id'];
    $course_image = null;

    if (isset($_FILES['new_course_image']) && $_FILES['new_course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['new_course_image']['tmp_name'];
        $name = basename($_FILES['new_course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_description, course_image, instructor_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id])) {
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p>Error creating course.</p>";
    }
}

if (isset($_GET['delete_course_id'])) {
    $course_id = $_GET['delete_course_id'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $enrollment_count = $stmt->fetchColumn();

    if ($enrollment_count > 0) {
        echo "<p>Cannot delete this course because there are existing enrollments.</p>";
    } else {
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        if ($stmt->execute([$course_id])) {
            echo "<p>Course deleted successfully!</p>";
        } else {
            echo "<p>Error deleting course.</p>";
        }
    }
}

$courses = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
    FROM courses c
")->fetchAll(PDO::FETCH_ASSOC);
$instructors = $pdo->query("SELECT * FROM instructors")->fetchAll(PDO::FETCH_ASSOC);
$students = $pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);
$total_courses = count($courses);
$total_instructors = count($instructors);
$total_students = count($students);
$filtered_instructors = $instructors;
$filtered_students = $students;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_instructor'])) {
    $instructor_name = $_POST['instructor_name'];
    $instructor_email = $_POST['instructor_email'];
    $instructor_password = $_POST['instructor_password'];
    $instructor_gender = $_POST['instructor_gender'];
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instructors WHERE email = ?");
    $stmt->execute([$instructor_email]);
    $email_exists = $stmt->fetchColumn();

    if ($email_exists) {
        echo "<script type='text/javascript'>
                alert('The email is already registered. Please use a different email.');
                window.location.href = 'admin.php';  // Redirect to the registration page to correct the email
              </script>";
    } else {
        $hashed_password = password_hash($instructor_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO instructors (name, email, password, gender) VALUES (?, ?, ?, ?)");

        if ($stmt->execute([$instructor_name, $instructor_email, $hashed_password, $instructor_gender])) {
            echo "<script type='text/javascript'>
                    alert('Instructor registered successfully!');
                    window.location.href = 'admin.php';  // Optional: Redirect after success
                  </script>";
        } else {
            echo "<p>Error registering instructor.</p>";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_certificate'])) {
    $course_id = $_POST['course_id'];
    $student_id = $_POST['student_id'];
    $certificate_file = null;

    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'certificates/';
        $tmp_name = $_FILES['certificate_file']['tmp_name'];
        $name = basename($_FILES['certificate_file']['name']);
        $certificate_file = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $certificate_file);

        $stmt = $pdo->prepare("INSERT INTO e_certificates (course_id, student_id, certificate_path) VALUES (?, ?, ?)");
        if ($stmt->execute([$course_id, $student_id, $certificate_file])) {
            echo "<p>Certificate uploaded successfully!</p>";
        } else {
            echo "<p>Error uploading certificate.</p>";
        }
    } else {
        echo "<p>Error in file upload.</p>";
    }
}

$courses_dropdown = $pdo->query("SELECT id, course_name FROM courses")->fetchAll(PDO::FETCH_ASSOC);
$selected_course_id = null;
$enrolled_students = [];
$assigned_instructors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_course'])) {
    $selected_course_id = $_POST['course_id'];
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.name AS student_name 
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        WHERE e.course_id = ?
    ");
    $stmt->execute([$selected_course_id]);
    $enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt = $pdo->prepare("
        SELECT i.id AS instructor_id, i.name AS instructor_name 
        FROM courses c
        JOIN instructors i ON c.instructor_id = i.id
        WHERE c.id = ?
    ");
    $stmt->execute([$selected_course_id]);
    $assigned_instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
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

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="./assets/admin.css">
    <link rel="stylesheet" type="text/css" href="./assets/theme.css">
    <title>Admin Dashboard</title>
</head>

<body>
    <section id="header">
        <div class="wrapper">
            <div class="header-container">
                <img src="./images/logo.png" class="logo" alt="Logo" />
                <div class="header-details">
                    <img src="./images/admin_logo.jpg" alt="Profile Logo" class="profile-logo" />
                    <span>Welcome, Admin</span>
                    <span>Last Login: <?php echo date('Y-m-d H:i:s'); ?></span>
                </div>
                <button class="logout-btn btn-secondary" id="logoutBtn">Logout</button>
            </div>
        </div>
    </section>
    <section id="admin">
        <div class="wrapper">
            <div class="admin-container">
                <div id="dashboard-stats">
                    <div class="stat-box">
                        <i class="bi bi-people"></i>
                        <div>Total Courses</div>
                        <div><?php echo $total_courses; ?></div>
                    </div>
                    <div class="stat-box">
                        <i class="bi bi-book"></i>
                        <div>Total Instructors</div>
                        <div><?php echo $total_instructors; ?></div>
                    </div>
                    <div class="stat-box">
                        <i class="bi bi-person"></i>
                        <div>Total Students</div>
                        <div><?php echo $total_students; ?></div>
                    </div>
                </div>
                <nav>
                    <ul>
                        <li><a href="#" class="tab-link" data-tab="manage-courses">Manage Courses</a></li>
                        <li><a href="#" class="tab-link" data-tab="add-new-instructors">Add New Instructors</a></li>
                        <li><a href="#" class="tab-link" data-tab="students">Students</a></li>
                        <li><a href="#" class="tab-link" data-tab="upload-certificates">Upload E-Certificates</a></li>
                        <li><a href="#" class="tab-link" data-tab="enrolled-students">Enrolled Students List Courses</a>
                        </li>
                        <li><a href="#" class="tab-link" data-tab="view-students">View Students</a>
                        </li>
                    </ul>
                </nav>
                <section class="tab-content" id="upload-certificates" style="display: none;">
                    <h2>Upload E-Certificates</h2>
                    <form method="POST" enctype="multipart/form-data">
                        <label for="course_id">Select Course:</label>
                        <select id="course_id" name="course_id" required>
                            <option value="">-- Select a Course --</option>
                            <?php foreach ($courses as $course): ?>
                                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <label for="student_id">Select Student:</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">-- Select a Student --</option>
                        </select>
                        <label for="certificate_file">Upload Certificate:</label>
                        <input type="file" name="certificate_file" id="certificate_file" accept=".pdf,.jpg,.png"
                            required>
                        <button type="submit" name="upload_certificate" class="btn-primary">Upload</button>
                    </form>
                </section>
                <section class="tab-content" id="view-students" style="display: none;">
                    <form method="POST" action="" style="margin-bottom: 10px;">
                        <select name="course_id">
                            <?php
                            $courses1 = $pdo->prepare("SELECT * FROM courses");
                            $courses1->execute();
                            $courses1 = $courses1->fetchAll(PDO::FETCH_ASSOC);
                            foreach ($courses1 as $course) {
                                var_dump($course);
                                echo "<option value='{$course['id']}'>{$course['course_name']}</option>";
                            }
                            ?>
                        </select>
                        <button type="submit" name="view_students" class="btn-primary">View Students</button>
                    </form>

                    <?php
                    if (isset($_POST['view_students'])) {
                        $course_id = $_POST['course_id'];
                        $students = $pdo->prepare("
                            SELECT 
                                students.*, 
                                COUNT(e_certificates.id) AS certificate_count 
                            FROM 
                                enrollments 
                            JOIN 
                                students ON students.id = enrollments.student_id 
                            LEFT JOIN 
                                e_certificates ON e_certificates.student_id = students.id AND e_certificates.course_id = enrollments.course_id
                            WHERE 
                                enrollments.course_id = ? 
                            GROUP BY 
                                students.id
                        ");
                        $students->execute([$course_id]);
                        $students = $students->fetchAll(PDO::FETCH_ASSOC);
                    ?>

                        <h3>Students List</h3>
                        <table border="1">
                            <thead>
                                <tr>
                                    <th>Learners</th>
                                    <th>Email</th>
                                    <th>Access Code</th>
                                    <th>Total Certificate</th>
                                    <th>Course Progress</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $progress = getStudentProgress($student['id'], $course_id, $pdo);

                                    if ($progress == 0) {
                                        $status = 'Waiting';
                                    } elseif ($progress == 100) {
                                        $status = 'Completed';
                                    } else {
                                        $status = 'Ongoing';
                                    }
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['code']); ?></td>
                                        <td><?php echo htmlspecialchars($student['certificate_count']); ?></td>
                                        <td><?php echo $status; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php
                    }
                    ?>
                </section>
                <section id="content">
                    <div id="enrolled-students" class="tab-content">
                        <h2>Enrolled Students and Assigned Instructors</h2>
                        <form method="POST">
                            <label for="course_id">Select Course:</label>
                            <select name="course_id" id="course_id" required>
                                <option value="" disabled selected>Choose a course</option>
                                <?php foreach ($courses_dropdown as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"
                                        <?php echo ($selected_course_id == $course['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['course_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="select_course" class="btn-primary">View</button>
                        </form>

                        <?php if ($selected_course_id): ?>
                            <h3>Students Enrolled in
                                "<?php echo htmlspecialchars($courses_dropdown[array_search($selected_course_id, array_column($courses_dropdown, 'id'))]['course_name']); ?>"
                            </h3>
                            <?php if (count($enrolled_students) > 0): ?>
                                <table class="table table-striped table-bordered">
                                    <h3>Assigned Instructor</h3>
                                    <?php if (count($assigned_instructors) > 0): ?>
                                        <ul>
                                            <?php foreach ($assigned_instructors as $instructor): ?>
                                                <li><?php echo htmlspecialchars($instructor['instructor_name']); ?></li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <p>No instructor assigned to this course.</p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <thead>
                                    <tr>
                                        <th>Student Name</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($enrolled_students as $student): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                </table>
                            <?php else: ?>
                                <p>No students enrolled in this course.</p>
                            <?php endif; ?>
                    </div>
                </section>
                <section id="manage-courses" class="tab-content active">
                    <h2>Manage Courses</h2>
                    <hr>
                    <h3>Create New Course</h3>
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="text" name="new_course_name" placeholder="Course Name" required>
                            <input type="text" name="new_course_description" placeholder="Course Description" required>
                            <input type="file" name="new_course_image" accept="image/*">
                            <select name="instructor_id" required>
                                <option value="">Select Instructor</option>
                                <?php foreach ($instructors as $instructor): ?>
                                    <option value="<?php echo $instructor['id']; ?>">
                                        <?php echo htmlspecialchars($instructor['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="submit" name="create_course" class="create-course-btn">Create Course</button>
                        </form>
                    </div>
                </section>
                <div class="course-list">
                    <?php foreach ($courses as $course): ?>
                        <div class="course">
                            <div class="course-info">
                                <h3 class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                                <div class="course-description">
                                    <?php echo htmlspecialchars($course['course_description']); ?>
                                </div>
                                <br><br>
                                <p>Enrolled Students: <?php echo $course['student_count']; ?></p><br><br>
                            </div>
                            <div class="course-actions">
                                <a class="edit-btn2" href="edit_course.php?course_id=<?php echo $course['id']; ?>">Edit</a>
                                <a class="delete-btn2" href="?delete_course_id=<?php echo $course['id']; ?>"
                                    onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <section id="add-new-instructors" class="tab-content ">
                    <h2>Add New Instructors</h2>
                    <div class="form-container">
                        <h3>Register New Instructor</h3>
                        <form method="POST" enctype="multipart/form-data">
                            <input type="text" name="instructor_name" placeholder="Instructor Name" required>
                            <input type="email" name="instructor_email" placeholder="Instructor Email" required>
                            <input type="password" name="instructor_password" placeholder="Instructor Password" required>
                            <select name="instructor_gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <button type="submit" name="register_instructor" class="registerbtn2">Register Instructor</button>
                        </form>
                    </div>
                    <hr>
                    <div class="instructor-list">
                        <h3>Instructors</h3>
                        <table>
                            <thead>
                                <tr>
                                    <th>Instructor Name</th>
                                    <th>Gender</th>
                                    <th>Email</th>
                                    <th>Assigned Course</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($instructors as $instructor): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($instructor['name']); ?></td>
                                        <td><?php echo htmlspecialchars($instructor['gender']); ?></td>
                                        <td><?php echo htmlspecialchars($instructor['email']); ?></td>
                                        <td>
                                            <?php
                                            $stmt = $pdo->prepare("SELECT course_name FROM courses WHERE instructor_id = ?");
                                            $stmt->execute([$instructor['id']]);
                                            $course = $stmt->fetch(PDO::FETCH_ASSOC);
                                            echo $course ? htmlspecialchars($course['course_name']) : 'Not Assigned';
                                            ?>
                                        </td>
                                        <td>
                                            <button
                                                onclick="openUpdateModal('<?php echo $instructor['id']; ?>', '<?php echo htmlspecialchars($instructor['name']); ?>', '<?php echo htmlspecialchars($instructor['email']); ?>', '<?php echo htmlspecialchars($instructor['gender']); ?>')"
                                                class="btn-update">
                                                Update
                                            </button>
                                            <form method="POST" action="delete_instructor.php" style="display:inline;" onsubmit="return confirmDelete();">
                                                <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                                                <button type="submit" class="btn-delete">Delete</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <div id="updateInstructorModal" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeModal()">&times;</span>
                        <h2>Update Instructor</h2>
                        <form method="POST" action="edit_instructor.php" enctype="multipart/form-data">
                            <input type="hidden" name="instructor_id" id="update_instructor_id">

                            <label for="update_instructor_name">Instructor Name</label>
                            <input type="text" id="update_instructor_name" name="instructor_name" required>

                            <label for="update_instructor_email">Email</label>
                            <input type="email" id="update_instructor_email" name="instructor_email" required>

                            <label for="update_instructor_gender">Gender</label>
                            <select id="update_instructor_gender" name="instructor_gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>

                            <label for="course_assignment">Assign Course</label>
                            <select id="course_assignment" name="course_id">
                                <option value="">Select a course</option>
                                <?php
                                foreach ($courses as $course): ?>
                                    <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label for="instructor_profile_picture">Profile Picture</label>
                            <input type="file" id="instructor_profile_picture" name="instructor_profile_picture">

                            <button type="submit">Update Instructor</button>
                        </form>
                    </div>
                </div>
                <?php
                if (isset($_GET['status'])) {
                    $status = $_GET['status'];
                    $message = '';
                }

                if (isset($_SESSION['status_message'])) {
                    echo "<script>alert('{$_SESSION['status_message']}');</script>";
                    unset($_SESSION['status_message']);
                }
                ?>
                <section id="students" class="tab-content">
                    <h2>Students</h2>
                    <div class="student-list">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Access Code</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <tr>
                                        <td><?php echo $student['id']; ?></td>
                                        <td><?php echo htmlspecialchars($student['name']); ?></td>
                                        <td><?php echo htmlspecialchars($student['email']); ?></td>
                                        <td><?php echo htmlspecialchars($student['code']); ?></td>
                                        <td>
                                            <?php echo ($student['approved'] == 1 ? 'Approved' : 'Pending'); ?>
                                        </td>
                                        <td>
                                            <?php if ($student['approved'] == 0): ?>
                                                <a href="approve.php?id=<?php echo $student['id']; ?>&action=approve"
                                                    class="approve-btn"
                                                    style="display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; background-color: #4CAF50; color: white; border: 1px solid #4CAF50; font-weight: bold; text-decoration: none; transition: background-color 0.3s ease, transform 0.3s ease;"
                                                    onmouseover="this.style.backgroundColor='#45a049'; this.style.transform='scale(1.05)';"
                                                    onmouseout="this.style.backgroundColor='#4CAF50'; this.style.transform='scale(1)';">Approve</a>
                                            <?php else: ?>
                                                <a href="approve.php?id=<?php echo $student['id']; ?>&action=deny"
                                                    class="deny-btn"
                                                    style="display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; background-color: #f44336; color: white; border: 1px solid #f44336; font-weight: bold; text-decoration: none; transition: background-color 0.3s ease, transform 0.3s ease;"
                                                    onmouseover="this.style.backgroundColor='#e53935'; this.style.transform='scale(1.05)';"
                                                    onmouseout="this.style.backgroundColor='#f44336'; this.style.transform='scale(1)';">Deny</a>
                                            <?php endif; ?>
                                            <button onclick="openEditModal(<?php echo $student['id']; ?>, '<?php echo htmlspecialchars($student['name']); ?>', '<?php echo htmlspecialchars($student['email']); ?>')"
                                                style="display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; background-color: #2196F3; color: white; border: 1px solid #2196F3; font-weight: bold; text-decoration: none; transition: background-color 0.3s ease, transform 0.3s ease;"
                                                onmouseover="this.style.backgroundColor='#1976D2'; this.style.transform='scale(1.05)';"
                                                onmouseout="this.style.backgroundColor='#2196F3'; this.style.transform='scale(1)';">Edit</button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
                <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.5);">
                    <div style="background-color: white; width: 50%; margin: 10% auto; padding: 20px; border-radius: 8px;">
                        <h3>Edit Student</h3>
                        <form id="editForm" method="POST" action="update_student.php">
                            <input type="hidden" name="id" id="editStudentId">
                            <div style="margin-bottom: 10px;">
                                <label for="editName" style="display: block; font-weight: bold;">Name:</label>
                                <input type="text" name="name" id="editName" style="width: 100%; padding: 8px;" required>
                            </div>
                            <div style="margin-bottom: 10px;">
                                <label for="editEmail" style="display: block; font-weight: bold;">Email:</label>
                                <input type="email" name="email" id="editEmail" style="width: 100%; padding: 8px;" required>
                            </div>
                            <button type="submit" style="padding: 10px 20px; background-color: #4CAF50; color: white; border: none; border-radius: 4px; cursor: pointer;">Save Changes</button>
                            <button type="button" onclick="closeEditModal()" style="padding: 10px 20px; background-color: #f44336; color: white; border: none; border-radius: 4px; cursor: pointer; margin-left: 10px;">Cancel</button>
                        </form>
                    </div>
                </div>
                <script>
                    function openEditModal(id, name, email) {
                        document.getElementById('editStudentId').value = id;
                        document.getElementById('editName').value = name;
                        document.getElementById('editEmail').value = email;
                        document.getElementById('editModal').style.display = 'block';
                    }

                    function closeEditModal() {
                        document.getElementById('editModal').style.display = 'none';
                    }
                </script>
                <section id="add-new-instructors" class="tab-content">
                    <h2>Register New Instructor</h2>
                    <div class="form-container">
                        <form method="POST" enctype="multipart/form-data">
                            <input type="text" name="instructor_name" placeholder="Instructor Name" required>
                            <input type="email" name="instructor_email" placeholder="Instructor Email" required>
                            <input type="password" name="instructor_password" placeholder="Instructor Password"
                                required>
                            <select name="instructor_gender" required>
                                <option value="" disabled selected>Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                            </select>
                            <button type="submit" name="register_instructor" class="btn-primary">Register Instructor</button>
                        </form>
                    </div>
                </section>
            </div>
        </div>
    </section>
    <script>
        $(document).ready(function() {
            $('.tab-link').click(function(e) {
                e.preventDefault();
                $('.tab-content').hide();
                $('#' + $(this).data('tab')).show();
            });

            $('#course_id').change(function() {
                const courseId = $(this).val();
                if (courseId) {
                    $.ajax({
                        url: 'fetch_students.php',
                        type: 'GET',
                        data: {
                            course_id: courseId
                        },
                        success: function(response) {
                            $('#student_id').html(response);
                        },
                        error: function() {
                            alert('Error fetching students.');
                        }
                    });
                } else {
                    $('#student_id').html('<option value="">-- Select a Student --</option>');
                }
            });

            const tabs = document.querySelectorAll('.tab-link');
            const tabContents = document.querySelectorAll('.tab-content');

            tabs.forEach(tab => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();

                    tabContents.forEach(content => {
                        content.classList.remove('active');
                    });

                    tabs.forEach(tab => {
                        tab.classList.remove('active');
                    });

                    const tabId = e.target.getAttribute('data-tab');
                    document.getElementById(tabId).classList.add('active');

                    e.target.classList.add('active');
                });
            });

            <?php if (!empty($message)): ?>
                alert("<?php echo $message; ?>");
            <?php endif; ?>

            function openEditModal(id, name, email, gender) {
                document.getElementById('edit_instructor_id').value = id;
                document.getElementById('edit_instructor_name').value = name;
                document.getElementById('edit_instructor_email').value = email;
                document.getElementById('edit_instructor_gender').value = gender;
                document.getElementById('editInstructorModal').style.display = 'block';
            }

            function closeModal() {
                document.getElementById('editInstructorModal').style.display = 'none';
            }

            window.onclick = function(event) {
                if (event.target === document.getElementById('editInstructorModal')) {
                    closeModal();
                }
            };

            var modal = document.getElementById("editInstructorModal");
            var header = document.querySelector(".modal-header");
            var offsetX, offsetY, isDragging = false;

            function closeModal() {
                modal.style.display = "none";
            }

            const tabLinks = document.querySelectorAll('.tab-link');

            tabLinks.forEach(link => {
                link.addEventListener('click', function() {
                    tabLinks.forEach(tab => tab.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            document.getElementById('logoutBtn').addEventListener('click', function() {
                Swal.fire({
                    title: 'Are you sure you want to log out?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, logout',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'logout.php';
                    }
                });
            });
        });
    </script>
</body>

</html>