<?php
session_start();

include 'db_connection.php';

// Check if student is logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];
$courses = $pdo->query("SELECT * FROM courses")->fetchAll(PDO::FETCH_ASSOC);

if (isset($_POST['enroll'])) {
    $course_id = $_POST['course_id'];
    $checkEnroll = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $checkEnroll->execute([$student_id, $course_id]);

    if ($checkEnroll->rowCount() == 0) {
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

if (isset($_POST['unenroll'])) {
    $course_id = $_POST['course_id'];
    $stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");

    if ($stmt->execute([$student_id, $course_id])) {
        echo "<p>Unenrolled successfully!</p>";
    } else {
        echo "<p>Unenrollment failed. Please try again.</p>";
    }
}

$enrolled_courses = $pdo->prepare("SELECT c.* FROM courses c
                                    JOIN enrollments e ON c.id = e.course_id
                                    WHERE e.student_id = ?");
$enrolled_courses->execute([$student_id]);
$enrolled_courses = $enrolled_courses->fetchAll(PDO::FETCH_ASSOC);

function getCourseProgress($pdo, $student_id, $course_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN 
                            (SELECT id FROM modules WHERE course_id = ?)");
    $stmt->execute([$student_id, $course_id]);
    return $stmt->fetchColumn();
}

function getCourseCompletionPercentage($pdo, $student_id, $course_id)
{
    $totalModulesStmt = $pdo->prepare("SELECT COUNT(*) FROM modules WHERE course_id = ?");
    $totalModulesStmt->execute([$course_id]);
    $totalModules = $totalModulesStmt->fetchColumn();
    $completedModulesStmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN 
                                            (SELECT id FROM modules WHERE course_id = ?)");
    $completedModulesStmt->execute([$student_id, $course_id]);
    $completedModules = $completedModulesStmt->fetchColumn();

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
            if ($fileSize < 5000000) {
                $newFileName = "profile_" . $student_id . "." . $fileExt;
                $fileDestination = 'uploads/' . $newFileName;

                if (!is_dir('uploads')) {
                    mkdir('uploads', 0777, true);
                }

                move_uploaded_file($fileTmpName, $fileDestination);

                $updatePic = $pdo->prepare("UPDATE students SET profile_pic = ? WHERE id = ?");
                if ($updatePic->execute([$fileDestination, $student_id])) {
                    $_SESSION['profile_pic'] = $fileDestination;
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

if (isset($_POST['update_gender'])) {
    $new_gender = $_POST['gender'];

    if (!in_array($new_gender, ['Male', 'Female', 'Other'])) {
        echo "<p>Invalid gender selection.</p>";
    } else {
        $updateGenderStmt = $pdo->prepare("UPDATE students SET gender = ? WHERE id = ?");
        if ($updateGenderStmt->execute([$new_gender, $student_id])) {
            echo "<p>Gender updated successfully!</p>";
        } else {
            echo "<p>Failed to update gender. Please try again.</p>";
        }
    }
}

$query = $pdo->prepare("SELECT DISTINCT c.course_name 
                        FROM courses c
                        JOIN enrollments e ON c.id = e.course_id
                        WHERE e.student_id = ?");
$query->execute([$student_id]);
$enrolled_courses = $query->fetchAll(PDO::FETCH_ASSOC);
$filter = isset($_POST['filter']) ? $_POST['filter'] : 'all';

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
            <img src="<?php echo !empty($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : './images/profile.png'; ?>"
                alt="Profile Image" class="profile-img" onclick="toggleForm()">

            <div class="profile-info">
                <h1><?php echo htmlspecialchars($_SESSION['student_name']); ?></h1>
                <p>Status: Registered Student</p>
            </div>
            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm" style="display: none;">
                <label for="profile_pic">Upload Profile Picture:</label>
                <input type="file" name="profile_pic" accept="image/*" required id="fileInput" onchange="previewImage(event)">
                <button type="submit" name="upload_pic">Upload</button>
            </form>
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

        <script>
            function previewImage(event) {
                var reader = new FileReader();
                reader.onload = function() {
                    var output = document.createElement('img');
                    output.src = reader.result;
                    output.alt = "Profile Image";
                    output.classList.add('profile-img');
                    output.style.width = '150px';
                    output.style.height = '150px';

                    var profileImage = document.querySelector('.profile-img');
                    profileImage.src = reader.result;
                };
                reader.readAsDataURL(event.target.files[0]);
            }
        </script>

        <script>
            function toggleForm() {
                var form = document.getElementById('uploadForm');
                if (form.style.display === 'none') {
                    form.style.display = 'block';
                } else {
                    form.style.display = 'none';
                }
            }
        </script>


        <script>
            function toggleForm() {
                const form = document.getElementById('uploadForm');
                form.style.display = form.style.display === 'block' ? 'none' : 'block';
            }
        </script>

    </main>

    <section class="courses">
        <h2>Your Enrolled Courses</h2>
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

                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p>You have not enrolled in any courses yet.</p>
        <?php endif; ?>

        <script>
            var enrolledCourses = document.querySelectorAll(".course");
            var selectedEnrolledCourse = null;

            enrolledCourses.forEach(function(course) {
                course.onclick = function() {
                    if (selectedEnrolledCourse) {
                        selectedEnrolledCourse.classList.remove("selected");
                    }

                    this.classList.add("selected");
                    selectedEnrolledCourse = this;
                };
            });
        </script>

    </section>
    </main>
</body>

</html>