<?php
// Database connection
session_start(); // Start session to access logged-in student data

include 'db_connection.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php"); // Redirect to login if not logged in
    exit();
}

$student_id = $_SESSION['student_id'];

// Fetch all courses
$courses = $pdo->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);

// Handle enrollment
if (isset($_POST['enroll'])) {
    $course_id = $_POST['course_id'];

    // Check if the student is already enrolled in the course
    $checkEnroll = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $checkEnroll->execute([$student_id, $course_id]);

    if ($checkEnroll->rowCount() == 0) {
        // Insert enrollment
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        if ($stmt->execute([$student_id, $course_id])) {
            echo "<p>Enrolled successfully!</p>";
        } else {
            echo "<p>Enrollment failed. Please try again.</p>";
        }
    } else {
        echo "<p>You are already enrolled in this course.</p>";
    }
}

// Handle unenrollment
if (isset($_POST['unenroll'])) {
    $course_id = $_POST['course_id'];

    // Remove the enrollment
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
    if ($stmt->execute([$student_id, $course_id])) {
        echo "<p>Unenrolled successfully!</p>";
    } else {
        echo "<p>Unenrollment failed. Please try again.</p>";
    }
}
// Fetch enrolled courses
$enrolled_courses = $pdo->prepare("SELECT c.* FROM courses c
                                    JOIN enrollments e ON c.id = e.course_id
                                    WHERE e.student_id = ?");
$enrolled_courses->execute([$student_id]);
$enrolled_courses = $enrolled_courses->fetchAll(PDO::FETCH_ASSOC);

// Function to get course progress
function getCourseProgress($pdo, $student_id, $course_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN 
                            (SELECT id FROM modules WHERE course_id = ?)");
    $stmt->execute([$student_id, $course_id]);
    return $stmt->fetchColumn(); // Returns the number of completed modules for this course
}



function getCourseCompletionPercentage($pdo, $student_id, $course_id) {
    // Get the total number of modules in the course
    $totalModulesStmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
    $totalModulesStmt->execute([$course_id]);
    $totalModules = $totalModulesStmt->fetchColumn();

    // Get the number of completed modules by the student in this course
    $completedModulesStmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN 
                                            (SELECT id FROM modules WHERE course_id = ?)");
    $completedModulesStmt->execute([$student_id, $course_id]);
    $completedModules = $completedModulesStmt->fetchColumn();

    // Calculate completion percentage
    return $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;
}


if (isset($_POST['upload_pic']) && isset($_FILES['profile_pic'])) {
    $file = $_FILES['profile_pic'];
    $fileName = $file['name'];
    $fileTmpName = $file['tmp_name'];
    $fileSize = $file['size'];
    $fileError = $file['error'];
    $fileType = $file['type'];
    
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    
    if (in_array($fileExt, $allowed)) {
        if ($fileError === 0) {
            if ($fileSize < 5000000) { // Limit file size to 5MB
                $newFileName = "profile_" . $student_id . "." . $fileExt;
                $fileDestination = 'uploads/' . $newFileName;
                
                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true); // Create uploads directory if not exists
                }
                
                move_uploaded_file($fileTmpName, $fileDestination);

                // Update the profile picture path in the database
                $updatePic = $pdo->prepare("UPDATE students SET profile_pic = ? WHERE id = ?");
                if ($updatePic->execute([$fileDestination, $student_id])) {
                    $_SESSION['profile_pic'] = $fileDestination; // Update session variable
                    echo "<p>Profile picture uploaded successfully!</p>";
                } else {
                    echo "<p>Failed to update profile picture in the database.</p>";
                }
            } else {
                echo "<p>Your file is too large. Maximum 5MB allowed.</p>";
            }
        } else {
            echo "<p>There was an error uploading your file.</p>";
        }
    } else {
        echo "<p>Invalid file type. Only JPG, JPEG, PNG, and GIF allowed.</p>";
    }
}

// Handle gender update
if (isset($_POST['update_gender'])) {
    $new_gender = $_POST['gender'];

    // Validate input
    if (!in_array($new_gender, ['Male', 'Female', 'Other'])) {
        echo "<p>Invalid gender selection.</p>";
    } else {
        // Update gender in the database
        $updateGenderStmt = $pdo->prepare("UPDATE students SET gender = ? WHERE id = ?");
        if ($updateGenderStmt->execute([$new_gender, $student_id])) {
            echo "<p>Gender updated successfully!</p>";
        } else {
            echo "<p>Failed to update gender. Please try again.</p>";
        }
    }
}
// Fetch the enrolled courses for the student
$query = $pdo->prepare("SELECT DISTINCT c.course_name 
                        FROM courses c
                        JOIN enrollments e ON c.id = e.course_id
                        WHERE e.student_id = ?");
$query->execute([$student_id]);
$enrolled_courses = $query->fetchAll(PDO::FETCH_ASSOC);

// Get the selected course filter
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

// Modify the query to filter courses by name based on the selected filter
if ($filter != 'all') {
    $courses = $pdo->prepare("SELECT * FROM courses WHERE course_name LIKE ? AND id IN (SELECT course_id FROM enrollments WHERE student_id = ?)");
    $courses->execute(["%$filter%", $student_id]);
} else {
    $courses = $pdo->prepare("SELECT * FROM courses WHERE id IN (SELECT course_id FROM enrollments WHERE student_id = ?)");
    $courses->execute([$student_id]);
}

$enrolled_courses_result = $courses->fetchAll(PDO::FETCH_ASSOC);

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - Hitoka Yachi</title>
    <link rel="stylesheet" type="text/css" href="student.css">
    </head>

<body>
<header>
<div class="logo">
    <a href="Student_courses.php">
        <img src="./images/logo.png" alt="e-Journo Eskwela" />
    </a>
</div>

        <nav>
           <ul class="nav-list">
    <li><a href="Student_courses.php">HOME</a></li>
</ul>
        </nav>
    </header>
    <main>
    <section class="profile-section">
    <!-- Display the current profile picture or default one -->
    <img src="<?php echo !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : './images/profile.png'; ?>" 
         alt="Profile Image" class="profile-img" onclick="toggleForm()">
    
    <div class="profile-info">
        <h1><?php echo htmlspecialchars($_SESSION['student_username']); ?></h1>
        <p>Status: Registered Student</p>
    </div>
    
    <!-- Upload Form -->
    <form action="" method="POST" enctype="multipart/form-data" id="uploadForm" style="display: none;">
        <label for="profile_pic">Upload Profile Picture:</label>
        <input type="file" name="profile_pic" accept="image/*" required id="fileInput" onchange="previewImage(event)">
        <button type="submit" name="upload_pic">Upload</button>
    </form>

    <!-- Gender Update Form -->
    <form method="POST" style="margin-top: 20px;">
        <label for="gender">Update Gender:</label>
        <select name="gender" id="gender" required>
            <option value="">Select Gender</option>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
            <option value="Other">Other</option>
        </select>
        <button type="submit" name="update_gender" class="btn">Update Gender</button>
    </form>
</section>

<!-- JavaScript for image preview -->
<script>
    function previewImage(event) {
        var reader = new FileReader();
        reader.onload = function() {
            var output = document.createElement('img');
            output.src = reader.result;
            output.alt = "Profile Image";
            output.classList.add('profile-img');
            output.style.width = '150px';  // Optional: Resize the preview image
            output.style.height = '150px'; // Optional: Resize the preview image

            // Replace the current profile image with the preview
            var profileImage = document.querySelector('.profile-img');
            profileImage.src = reader.result; // Update the image source to the previewed one
        };
        reader.readAsDataURL(event.target.files[0]);
    }
</script>

<script>
    // Function to toggle the form visibility
    function toggleForm() {
        var form = document.getElementById('uploadForm');
        // Toggle the form visibility
        if (form.style.display === 'none') {
            form.style.display = 'block';
        } else {
            form.style.display = 'none';
        }
    }
</script>


<script>
    // JavaScript function to toggle form visibility
    function toggleForm() {
        const form = document.getElementById('uploadForm');
        form.style.display = form.style.display === 'block' ? 'none' : 'block';
    }
</script>

    </main>    

    <section class="courses">
    <h2>Your Enrolled Courses</h2>
         <!-- Filter Form -->
            <!-- Filter Form -->
            <form method="POST" action="" class="filter-form">
            <select name="filter" onchange="this.form.submit()">
                <option value="all">All</option>
                <?php foreach ($enrolled_courses as $enrolled_course): ?>
                    <option value="<?php echo htmlspecialchars($enrolled_course['course_name']); ?>" 
                        <?php echo ($filter == $enrolled_course['course_name']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($enrolled_course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

<?php if (count($enrolled_courses_result) > 0): ?>
    <div class="course-list">
        <?php foreach ($enrolled_courses_result as $enrolled_course): ?>
            <div class="course" data-course-id="<?php echo $enrolled_course['id']; ?>">
                <h3><?php echo htmlspecialchars($enrolled_course['course_name']); ?></h3>
                <form method="POST" action="" class="unenroll-form">
                    <input type="hidden" name="course_id" value="<?php echo $enrolled_course['id']; ?>">
                    <input type="submit" name="unenroll" value="Unenroll" class="button unenroll">
                </form>
                <a class="view-coursebutton" href="courses.php?course_id=<?php echo $enrolled_course['id']; ?>">View Course</a>
                <!-- Progress Bar -->
            
            </div>
        <?php endforeach; ?>
    </div>
<?php else: ?>
    <p>You have not enrolled in any courses yet.</p>
<?php endif; ?>

<script>
    // Highlight selected course in enrolled courses
    var enrolledCourses = document.querySelectorAll(".course");
    var selectedEnrolledCourse = null;

    // When the user clicks an enrolled course card
    enrolledCourses.forEach(function(course) {
        course.onclick = function() {
            // Remove highlight from previously selected course
            if (selectedEnrolledCourse) {
                selectedEnrolledCourse.classList.remove("selected");
            }

            // Highlight the current course
            this.classList.add("selected");
            selectedEnrolledCourse = this;
        };
    });
</script>

    </section>
</main>
<style>
    /* Add this CSS to your existing styles or in a new style section */
.filter-form select {
    width: 150px; /* Adjust this width as needed */
    padding: 5px;  /* Optional: adds some padding to the select box */
    font-size: 14px; /* Optional: adjusts font size */
    border-radius: 5px; /* Optional: rounds the corners */
    border: 1px solid #ccc; /* Optional: adds a border */
}
.course {
    cursor: pointer;
    border: 1px solid #ccc;
    padding: 10px;
    margin: 10px;
    background-color: #4169e1;
    border-radius: 5px;
    transition: transform 0.2s, background-color 0.2s;
    height: 120px;
}

.course:hover {
    background-color: #f0f0f0;
    transform: scale(1.02);
}

.course.selected {
    background-color: #d1ecf1; /* Light blue background */
    border: 2px solid #007bff; /* Blue border */
    transform: scale(1.03); /* Slightly larger for emphasis */
}

.course h3 {
    margin: 0;
    text-align: center;
}

.progress-bar-container {
    width: 100%;
    background-color: #e0e0e0;
    border-radius: 10px;
    margin-top: 10px;
    overflow: hidden;
    height: 10px;
}

.progress-bar {
    height: 10px;
    background-color: #007bff;
    border-radius: 10px;
    transition: width 0.3s ease;
}


</style>

   
</body>
</html>