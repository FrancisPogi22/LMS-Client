<?php
// Database connection
include 'db_connection.php';

// Handle course update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_id = $_POST['course_id'];
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];
    $instructor_id = $_POST['instructor_id'];

    // Handle file upload for course image
    $course_image = null;
    if (isset($_FILES['course_image']) && $_FILES['course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['course_image']['tmp_name'];
        $name = basename($_FILES['course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    // Update course details along with the assigned instructor
    $stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ?, course_image = ?, instructor_id = ? WHERE id = ?");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id, $course_id])) {
        echo "<p>Course updated successfully!</p>";
    } else {
        echo "<p>Error updating course.</p>";
    }
}

// Handle course creation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_course'])) {
    $course_name = $_POST['new_course_name'];
    $course_description = $_POST['new_course_description'];
    $instructor_id = $_POST['instructor_id'];

    // Handle file upload for course image
    $course_image = null;
    if (isset($_FILES['new_course_image']) && $_FILES['new_course_image']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'uploads/';
        $tmp_name = $_FILES['new_course_image']['tmp_name'];
        $name = basename($_FILES['new_course_image']['name']);
        $course_image = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $course_image);
    }

    // Insert course with assigned instructor
    $stmt = $pdo->prepare("INSERT INTO courses (course_name, course_description, course_image, instructor_id) VALUES (?, ?, ?, ?)");
    if ($stmt->execute([$course_name, $course_description, $course_image, $instructor_id])) {
        // Redirect to avoid duplicate form submission on page refresh
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        echo "<p>Error creating course.</p>";
    }
}


// Handle course deletion
if (isset($_GET['delete_course_id'])) {
    $course_id = $_GET['delete_course_id'];

    // Check for existing enrollments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM enrollments WHERE course_id = ?");
    $stmt->execute([$course_id]);
    $enrollment_count = $stmt->fetchColumn();

    if ($enrollment_count > 0) {
        echo "<p>Cannot delete this course because there are existing enrollments.</p>";
    } else {
        // Now, delete the course
        $stmt = $pdo->prepare("DELETE FROM courses WHERE id = ?");
        if ($stmt->execute([$course_id])) {
            echo "<p>Course deleted successfully!</p>";
        } else {
            echo "<p>Error deleting course.</p>";
        }
    }
}

// Fetch all courses and student count for each course
$courses = $pdo->query("
    SELECT c.*, 
           (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS student_count
    FROM courses c
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all instructors
$instructors = $pdo->query("SELECT * FROM instructors")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all students
$students = $pdo->query("SELECT * FROM students")->fetchAll(PDO::FETCH_ASSOC);

// Count totals
$total_courses = count($courses);
$total_instructors = count($instructors);
$total_students = count($students);
// Initialize variables for filtered results
$filtered_instructors = $instructors; // Default to all instructors
$filtered_students = $students; // Default to all students

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_instructor'])) {
    $instructor_name = $_POST['instructor_name'];
    $instructor_email = $_POST['instructor_email'];
    $instructor_password = $_POST['instructor_password'];
    $instructor_gender = $_POST['instructor_gender']; // Get the gender value from the form

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM instructors WHERE email = ?");
    $stmt->execute([$instructor_email]);
    $email_exists = $stmt->fetchColumn();

    if ($email_exists) {
        // Email already exists, show alert
        echo "<script type='text/javascript'>
                alert('The email is already registered. Please use a different email.');
                window.location.href = 'admin.php';  // Redirect to the registration page to correct the email
              </script>";
    } else {
        // Hash the password before storing it
        $hashed_password = password_hash($instructor_password, PASSWORD_DEFAULT);

        // Insert new instructor into the database (including gender)
        $stmt = $pdo->prepare("INSERT INTO instructors (name, email, password, gender) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$instructor_name, $instructor_email, $hashed_password, $instructor_gender])) {
            // Alert message on successful registration
            echo "<script type='text/javascript'>
                    alert('Instructor registered successfully!');
                    window.location.href = 'admin.php';  // Optional: Redirect after success
                  </script>";
        } else {
            echo "<p>Error registering instructor.</p>";
        }
    }
}
// Handle e-certificate upload
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['upload_certificate'])) {
    $course_id = $_POST['course_id'];
    $student_id = $_POST['student_id'];
    $certificate_file = null;

    // Handle file upload
    if (isset($_FILES['certificate_file']) && $_FILES['certificate_file']['error'] == UPLOAD_ERR_OK) {
        $uploads_dir = 'certificates/';
        $tmp_name = $_FILES['certificate_file']['tmp_name'];
        $name = basename($_FILES['certificate_file']['name']);
        $certificate_file = $uploads_dir . $name;

        move_uploaded_file($tmp_name, $certificate_file);

        // Save certificate data in the database
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
// Fetch all courses for the dropdown
$courses_dropdown = $pdo->query("SELECT id, course_name FROM courses")->fetchAll(PDO::FETCH_ASSOC);

// Fetch enrolled students and instructors based on selected course
$selected_course_id = null;
$enrolled_students = [];
$assigned_instructors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['select_course'])) {
    $selected_course_id = $_POST['course_id'];

    // Fetch enrolled students for the selected course
    $stmt = $pdo->prepare("
        SELECT s.id AS student_id, s.name AS student_name 
        FROM enrollments e
        JOIN students s ON e.student_id = s.id
        WHERE e.course_id = ?
    ");
    $stmt->execute([$selected_course_id]);
    $enrolled_students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch the instructor for the selected course
    $stmt = $pdo->prepare("
        SELECT i.id AS instructor_id, i.name AS instructor_name 
        FROM courses c
        JOIN instructors i ON c.instructor_id = i.id
        WHERE c.id = ?
    ");
    $stmt->execute([$selected_course_id]);
    $assigned_instructors = $stmt->fetchAll(PDO::FETCH_ASSOC);
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/jquery@3.6.0/dist/jquery.min.js"></script> <!-- Include jQuery -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Include SweetAlert2 -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<link rel="stylesheet" type="text/css" href="admin.css">

    <title>Admin Dashboard</title>
  
</head>
<body>
<header>
    <img src="./images/logo.png" alt="Logo" id="logoImage" />
    <div class="span">
        <span>Welcome, Admin <img src="./images/admin_logo.jpg" alt="Profile Logo" class="profile-logo" /></span>
        <span>Last Login: <?php echo date('Y-m-d H:i:s'); ?></span>
    </div>
    <button class="logout-btn" id="logoutBtn">Logout</button>
    
<script>
    // Adding SweetAlert for logout confirmation when clicking on the logout button
    document.getElementById('logoutBtn').addEventListener('click', function () {
        Swal.fire({
            title: 'Are you sure you want to log out?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, logout',
            cancelButtonText: 'Cancel',
        }).then((result) => {
            if (result.isConfirmed) {
                // Redirect to logout page if confirmed
                window.location.href = 'logout.php';
            }
        });
    });
</script>
</header>
<div class="container">
    <div id="dashboard-stats">
        <div class="stat-box">
            <img src="./images/admin_user.png" alt="Courses Icon" /> <!-- Replace with your icon path -->
            <div>Total Courses</div>
            <div><?php echo $total_courses; ?></div>
        </div>
        <div class="stat-box">
            <img src="./images/admin_courses.png" alt="Instructors Icon" /> <!-- Replace with your icon path -->
            <div>Total Instructors</div>
            <div><?php echo $total_instructors; ?></div>
        </div>
        <div class="stat-box">
            <img src="./images/admin_instrutors.png" alt="Students Icon" /> <!-- Replace with your icon path -->
            <div>Total Students</div>
            <div><?php echo $total_students; ?></div>
        </div>
    </div>
</div>


<nav>
    <ul>
        <li><a href="#" class="tab-link" data-tab="manage-courses">Manage Courses</a></li>
        <li><a href="#" class="tab-link" data-tab="create-course">Create Course</a></li>
        <li><a href="#" class="tab-link" data-tab="instructors">Instructors</a></li>
        <li><a href="#" class="tab-link" data-tab="students">Students</a></li>
        <li><a href="#" class="tab-link" data-tab="register-instructor">Register New Instructor</a></li>
        <li><a href="#" class="tab-link" data-tab="upload-certificates">Upload E-Certificates</a></li>
        <li><a href="#" class="tab-link" data-tab="enrolled-students">Enrolled Students List Courses</a></li>
        </ul>
</nav>

</nav>

<div class="tab-content" id="upload-certificates" style="display: none;">
    <h2>Upload E-Certificates</h2>
    <form method="POST" enctype="multipart/form-data">
        <label for="course_id">Select Course:</label>
        <select id="course_id" name="course_id" required>
            <option value="">-- Select a Course --</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?= $course['id'] ?>"><?= htmlspecialchars($course['course_name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="student_id">Select Student:</label>
        <select id="student_id" name="student_id" required>
            <option value="">-- Select a Student --</option>
        </select>

        <label for="certificate_file">Upload Certificate:</label>
        <input type="file" name="certificate_file" id="certificate_file" accept=".pdf,.jpg,.png" required>

        <button type="submit" name="upload_certificate">Upload</button>
    </form>
</div>

<script>
    $(document).ready(function () {
        // Show/hide tabs
        $('.tab-link').click(function (e) {
            e.preventDefault();
            $('.tab-content').hide();
            $('#' + $(this).data('tab')).show();
        });

        // Fetch students when a course is selected
        $('#course_id').change(function () {
            const courseId = $(this).val();
            if (courseId) {
                $.ajax({
                    url: 'fetch_students.php',
                    type: 'GET',
                    data: {course_id: courseId},
                    success: function (response) {
                        $('#student_id').html(response);
                    },
                    error: function () {
                        alert('Error fetching students.');
                    }
                });
            } else {
                $('#student_id').html('<option value="">-- Select a Student --</option>');
            }
        });
    });
</script>
<!-- list of students in courses -->
<div id="content">
       <!-- Enrolled Students List Courses Tab -->
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
        <button type="submit" name="select_course">View</button>
    </form>

    <?php if ($selected_course_id): ?>
        <h3>Students Enrolled in "<?php echo htmlspecialchars($courses_dropdown[array_search($selected_course_id, array_column($courses_dropdown, 'id'))]['course_name']); ?>"</h3>
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

<style>
        
        table {
    width: 70%;
    border-collapse: collapse;
    margin-left: 10vh;
}

table, th, td {
    border: 2px solid black;
}

th, td {
    padding: 8px;
    text-align: left;
}

thead {
    background-color: #f2f2f2;
}

    </style>
<!-- e E-Certificates -->

<script>
    // Get all the tab links
    const tabLinks = document.querySelectorAll('.tab-link');

    tabLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Remove active class from all tabs
            tabLinks.forEach(tab => tab.classList.remove('active'));
            
            // Add active class to the clicked tab
            this.classList.add('active');
        });
    });
</script>

        <section id="manage-courses" class="tab-content active">
    <h2>Manage Courses</h2>
    <div class="course-list">
        <?php foreach ($courses as $course): ?>
            <div class="course">
                <div class="course-info">
                    <h3 class="course-title"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <div class="course-description">
                        <?php echo htmlspecialchars($course['course_description']); ?>
                    </div>
                    <br><br><p>Enrolled Students: <?php echo $course['student_count']; ?></p><br><br>
                </div>
                <div class="course-actions">
                    <a class="edit-btn" href="edit_course.php?course_id=<?php echo $course['id']; ?>">Edit</a>
                    <a class="delete-btn" href="?delete_course_id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">Delete</a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<style>
    /* Style for all tabs */
nav ul li a {
    text-decoration: none;
    padding: 10px 15px;
    display: block;
    font-size: 16px;
    color: white;
    transition: background-color 0.3s ease, color 0.3s ease;
}

/* Hover effect */
nav ul li a:hover {
    background-color: #4CAF50;  /* Green background */
    color: white;
}

/* Active tab style */
nav ul li a.active {
    background-color: #45a049;  /* Slightly darker green for active tab */
    color: white;
    font-weight: bold;
}
.course-title{
    color: white;
}
    .course {
        position: relative; /* Position the action buttons relative to this container */
        margin-bottom: 20px; /* Add space between courses */
        padding-bottom: 50px; /* Add space for the buttons at the bottom */
        border: 1px solid #ddd; /* Optional: Add border to course box */
        padding: 15px;
        border-radius: 5px;
        width: 600px;
        height: 30vh;
    }

    .course-info {
        padding-bottom: 40px; /* Space for the action buttons at the bottom */
    }

    .course-description {
        max-height: 100px; /* Limit height of the description */
        overflow-y: auto; /* Add a vertical scrollbar if content exceeds max-height */
        padding: 5px;
        background-color: white; /* Optional: Light background for better readability */
        margin-top: 50px;
        border-radius: 10px;
        
    }

    .course-actions {
        position: absolute;
        bottom: 10px; /* Place buttons at the bottom of the container */
        right: 10px; /* Align buttons to the right */
        text-align: right;
    }

    .edit-btn,
    .delete-btn {
        padding: 5px 10px;
        margin-left: 10px;
        text-decoration: none;
        color: #fff;
        border-radius: 5px;
        cursor: pointer;
     
    }

    .edit-btn {
        background-color: #4CAF50;
    }

    .delete-btn {
        background-color: #f44336;
    }
</style>

    

        <section id="create-course" class="tab-content">
            <h2>Create Course</h2>
            <div class="form-container">
                <form method="POST" enctype="multipart/form-data">
                    <input type="text" name="new_course_name" placeholder="Course Name" required>
                    <input type="text" name="new_course_description" placeholder="Course Description" required>
                    <input type="file" name="new_course_image" accept="image/*">
                    <select name="instructor_id" required>
                        <option value="">Select Instructor</option>
                        <?php foreach ($instructors as $instructor): ?>
                            <option value="<?php echo $instructor['id']; ?>"><?php echo htmlspecialchars($instructor['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="create_course">Create Course</button>
                </form>
            </div>
        </section>

        <section id="instructors" class="tab-content">
    <div class="header">
        <h2>Instructors</h2>
    </div>


    <div class="instructor-list">
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
                            <!-- Edit Instructor Button to open modal -->
                            <button onclick="openEditModal('<?php echo $instructor['id']; ?>', '<?php echo htmlspecialchars($instructor['name']); ?>', '<?php echo htmlspecialchars($instructor['email']); ?>', '<?php echo htmlspecialchars($instructor['gender']); ?>')" class="btn-edit">Edit</button>
                            
                            <!-- Delete Instructor Button -->
                            <form method="POST" action="delete_instructor.php" style="display:inline;">
                                <input type="hidden" name="instructor_id" value="<?php echo $instructor['id']; ?>">
                                <button type="submit" onclick="return confirm('Are you sure you want to delete this instructor?');">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<!-- JavaScript to handle the refresh functionality for instructors only -->
<script>
    function refreshInstructors() {
        // Check if the user is an instructor (you may replace this with a real user role check)
        var userRole = "<?php echo $_SESSION['role']; ?>"; // Assuming the user role is stored in the session
        
        if (userRole !== "instructor") {
            alert("Only instructors can refresh the instructor list.");
            return;
        }

        // Create a new XMLHttpRequest object to fetch the updated instructors list
        var xhr = new XMLHttpRequest();
        xhr.open('GET', 'fetch_instructors.php', true);

        // Set the callback function to update the instructor table when data is received
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4 && xhr.status === 200) {
                // Replace the current instructor table body with the updated content
                document.getElementById('instructor-table-body').innerHTML = xhr.responseText;
            }
        };

        // Send the request to fetch the instructor data
        xhr.send();
    }
</script>

<style>
    .btn-refresh {
        font-size: 14px;
        padding: 6px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        background-color: #002193;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 10%;
        margin-right: 10px;
    }

    .btn-refresh:hover {
        background-color: #e0e0e0;
    }
</style>

<!-- Modal for editing an instructor -->
<div id="editInstructorModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>Edit Instructor</h2>
        
        <!-- Form content -->
        <form method="POST" action="edit_instructor.php" enctype="multipart/form-data">
            <input type="hidden" name="instructor_id" id="edit_instructor_id">
            
            <label for="edit_instructor_name">Instructor Name</label>
            <input type="text" id="edit_instructor_name" name="instructor_name" required>
            
            <label for="edit_instructor_email">Email</label>
            <input type="email" id="edit_instructor_email" name="instructor_email" required>
            
            <label for="edit_instructor_gender">Gender</label>
            <select id="edit_instructor_gender" name="instructor_gender" required>
                <option value="">Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <option value="other">Other</option>
            </select>
            
            <label for="course_assignment">Assign Course</label>
            <select id="course_assignment" name="course_id">
                <option value="">Select a course</option>
                <?php
                    // Assuming you have a $courses array containing courses
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



<!-- JavaScript for Modal -->
<script>
    // Open the modal and populate it with the instructor's data
    function openEditModal(id, name, email, gender) {
        document.getElementById('edit_instructor_id').value = id;
        document.getElementById('edit_instructor_name').value = name;
        document.getElementById('edit_instructor_email').value = email;
        document.getElementById('edit_instructor_gender').value = gender;
        document.getElementById('editInstructorModal').style.display = 'block';
    }

    // Close the modal
    function closeModal() {
        document.getElementById('editInstructorModal').style.display = 'none';
    }

    // Close the modal if user clicks outside of it
    window.onclick = function(event) {
        if (event.target === document.getElementById('editInstructorModal')) {
            closeModal();
        }
    };
    // Get the modal and header for dragging
var modal = document.getElementById("editInstructorModal");
var header = document.querySelector(".modal-header");

// Variables to store the position of the modal
var offsetX, offsetY, isDragging = false;

// When the user presses down on the modal header, start dragging
header.onmousedown = function(e) {
    isDragging = true;
    offsetX = e.clientX - modal.offsetLeft;
    offsetY = e.clientY - modal.offsetTop;
    
    // Prevent selection while dragging
    document.onselectstart = function() { return false; };
}

// When the user moves the mouse, move the modal if dragging
document.onmousemove = function(e) {
    if (isDragging) {
        modal.style.left = e.clientX - offsetX + "px";
        modal.style.top = e.clientY - offsetY + "px";
    }
}

// When the user releases the mouse, stop dragging
document.onmouseup = function() {
    isDragging = false;
    document.onselectstart = null;
}

// Close Modal Function
function closeModal() {
    modal.style.display = "none";
}

</script>






<?php
// Start session to use flash messages

// admin.php

// Check if there is a success or error message
if (isset($_GET['status'])) {
    $status = $_GET['status'];
    $message = '';

    // Set the message based on the status
 
}


// Display the message if it exists in the session
if (isset($_SESSION['status_message'])) {
    echo "<script>alert('{$_SESSION['status_message']}');</script>";
    unset($_SESSION['status_message']); // Clear the message after displaying it
}
?>


<script type="text/javascript">
    <?php if (!empty($message)): ?>
        alert("<?php echo $message; ?>");
    <?php endif; ?>
</script>

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
                            <!-- Approve button link -->
                            <a href="approve.php?id=<?php echo $student['id']; ?>&action=approve" class="approve-btn" style="display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; background-color: #4CAF50; color: white; border: 1px solid #4CAF50; font-weight: bold; text-decoration: none; transition: background-color 0.3s ease, transform 0.3s ease;" onmouseover="this.style.backgroundColor='#45a049'; this.style.transform='scale(1.05)';" onmouseout="this.style.backgroundColor='#4CAF50'; this.style.transform='scale(1)';">Approve</a>
                            <?php else: ?>
                                <!-- Deny button link -->
                                <a href="approve.php?id=<?php echo $student['id']; ?>&action=deny" class="deny-btn" style="display: inline-block; padding: 10px 20px; margin: 5px; border-radius: 5px; background-color: #f44336; color: white; border: 1px solid #f44336; font-weight: bold; text-decoration: none; transition: background-color 0.3s ease, transform 0.3s ease;" onmouseover="this.style.backgroundColor='#e53935'; this.style.transform='scale(1.05)';" onmouseout="this.style.backgroundColor='#f44336'; this.style.transform='scale(1)';">Deny</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>


<section id="register-instructor" class="tab-content">
    <h2>Register New Instructor</h2>
    <div class="form-container">
        <form method="POST" enctype="multipart/form-data">
            <input type="text" name="instructor_name" placeholder="Instructor Name" required>
            <input type="email" name="instructor_email" placeholder="Instructor Email" required>
            <input type="password" name="instructor_password" placeholder="Instructor Password" required>
            
            <!-- Gender dropdown -->
            <select name="instructor_gender" required>
                <option value="" disabled selected>Select Gender</option>
                <option value="male">Male</option>
                <option value="female">Female</option>
                <!-- Add other options if your enum allows more values -->
            </select>

            <button type="submit" name="register_instructor">Register Instructor</button>
        </form>
    </div>
</section>


    <script>
        // Tab switching logic
        const tabs = document.querySelectorAll('.tab-link');
        const tabContents = document.querySelectorAll('.tab-content');

        tabs.forEach(tab => {
            tab.addEventListener('click', (e) => {
                e.preventDefault();

                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.remove('active');
                });

                // Remove active class from all tabs
                tabs.forEach(tab => {
                    tab.classList.remove('active');
                });

                // Show the clicked tab content
                const tabId = e.target.getAttribute('data-tab');
                document.getElementById(tabId).classList.add('active');

                // Add active class to clicked tab
                e.target.classList.add('active');
            });
        });
    </script>
    <style>
    .swal2-title{
        background-color: white;
    }
</style>
</body>
</html>