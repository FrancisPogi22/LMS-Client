<?php
// Database connection
include 'db_connection.php';
session_start();

// Redirect if not logged in
if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

$student_id = $_SESSION['student_id'];

// Function to fetch modules for a course
function getModulesByCourseId($course_id, $pdo)
{
    $query = "SELECT * FROM modules WHERE course_id = :course_id";
    $statement = $pdo->prepare($query);
    $statement->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $statement->execute();
    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

// Handle enrollment
if (isset($_POST['enroll'])) {
    $course_id = $_POST['course_id'];
    $checkEnroll = $pdo->prepare("SELECT * FROM enrollments WHERE student_id = ? AND course_id = ?");
    $checkEnroll->execute([$student_id, $course_id]);

    if ($checkEnroll->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO enrollments (student_id, course_id) VALUES (?, ?)");
        $_SESSION['enrollment_status'] = $stmt->execute([$student_id, $course_id]) ? 'success' : 'error';
    } else {
        $_SESSION['enrollment_status'] = 'already_enrolled';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Include SweetAlert for notifications
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['enrollment_status'])):
    $status = $_SESSION['enrollment_status'];
    unset($_SESSION['enrollment_status']);
?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const messages = {
                success: {
                    title: 'Success!',
                    text: 'Enrolled successfully!',
                    icon: 'success'
                },
                already_enrolled: {
                    title: 'Notice',
                    text: 'You are already enrolled in this course.',
                    icon: 'info'
                },
                error: {
                    title: 'Error',
                    text: 'Enrollment failed. Please try again.',
                    icon: 'error'
                },
            };
            Swal.fire(messages['<?php echo $status; ?>']);
        });
    </script>
<?php
endif;

// Search functionality
$searchTerm = $_GET['search'] ?? '';
$query = $searchTerm ? "SELECT * FROM courses WHERE course_name LIKE ?" : "SELECT * FROM courses";
$coursesStmt = $pdo->prepare($query);
$coursesStmt->execute($searchTerm ? ["%$searchTerm%"] : []);
$courses = $coursesStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch modules for all courses
$courseModules = [];
foreach ($courses as $course) {
    $courseModules[$course['id']] = getModulesByCourseId($course['id'], $pdo);
}

function getModuleStatus($student_id, $module_id, $pdo)
{
    $query = "SELECT is_done FROM module_completion WHERE student_id = ? AND module_id = ?";
    $stmt = $pdo->prepare($query);
    $stmt->execute([$student_id, $module_id]);
    return $stmt->fetchColumn();
}

// Handle Mark as Done
if (isset($_POST['mark_done'])) {
    $module_id = $_POST['module_id'];

    $checkStatus = $pdo->prepare("SELECT * FROM module_completion WHERE student_id = ? AND module_id = ?");
    $checkStatus->execute([$student_id, $module_id]);

    if ($checkStatus->rowCount() == 0) {
        $stmt = $pdo->prepare("INSERT INTO module_completion (student_id, module_id, is_done) VALUES (?, ?, 1)");
        $stmt->execute([$student_id, $module_id]);
    } else {
        $stmt = $pdo->prepare("UPDATE module_completion SET is_done = 1 WHERE student_id = ? AND module_id = ?");
        $stmt->execute([$student_id, $module_id]);
    }

    $_SESSION['mark_done_status'] = 'done';
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

function getStudentProgress($student_id, $course_id, $pdo)
{
    $query = "SELECT m.id AS module_id, mc.is_done 
              FROM modules m
              LEFT JOIN module_completion mc ON m.id = mc.module_id AND mc.student_id = :student_id
              WHERE m.course_id = :course_id";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':course_id', $course_id, PDO::PARAM_INT);
    $stmt->execute();
    $modules = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalModules = count($modules);
    $completedModules = 0;
    foreach ($modules as $module) {
        if ($module['is_done'] == 1) {
            $completedModules++;
        }
    }
    $progress = $totalModules > 0 ? ($completedModules / $totalModules) * 100 : 0;
    return round($progress, 2);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Courses</title>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="student.css">
    <link rel="stylesheet" type="text/css" href="./assets/theme.css">
</head>

<body>
    <header>
        <div class="logo">
            <img src="./images/logo.png" alt="e-Journo Eskwela" />
        </div>
        <nav>
            <ul>
                <li><a href="show_courses.php">Courses</a></li>
                <li><a href="about.php">ABOUT US</a></li>
                <?php if (isset($_SESSION['student_username'])): ?>
                    <li><a href="profile_students.php"><i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['student_name']); ?></a></li>
                    <li><a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                <?php else: ?>
                    <li><a href="student_login.php">Login</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>
    <main>
        <h1>COURSES</h1>
        <div class="container" id="coursesContainer">
            <?php foreach ($courses as $course): ?>
                <div class="course-item">
                    <h2><?php echo htmlspecialchars($course['course_name']); ?></h2>
                    <p><?php echo htmlspecialchars($course['course_description']); ?></p>
                    <?php
                    $progress = getStudentProgress($student_id, $course['id'], $pdo);
                    ?>
                    <div class="progress-bar">
                        <div class="progress" style="width: <?php echo $progress; ?>%;"></div>
                    </div>
                    <p>Progress: <?php echo round($progress, 2); ?>%</p>
                    <?php if (!empty($courseModules[$course['id']])): ?>
                        <h3>Modules:</h3>
                        <ul>
                            <?php foreach ($courseModules[$course['id']] as $module): ?>
                                <li>
                                    <?php echo htmlspecialchars($module['title']); ?>
                                    <?php if (getModuleStatus($student_id, $module['id'], $pdo)): ?>
                                        <span style="color: green; font-weight: bold;">(Done)</span>
                                    <?php else: ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="module_id" value="<?php echo $module['id']; ?>">
                                            <button type="submit" name="mark_done" class="btn-primary">Mark as Done</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p>No modules available for this course.</p>
                    <?php endif; ?>

                    <form method="POST" style="margin-top: 10px;">
                        <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                        <button type="submit" name="enroll" class="enroll-btn">Enroll</button>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </main>
    <script>
        // Logout confirmation
        document.getElementById("logout").addEventListener("click", function(event) {
            event.preventDefault();
            Swal.fire({
                title: 'Are you sure you want to logout?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, logout',
            }).then((result) => {
                if (result.isConfirmed) window.location.href = 'student_login.php';
            });
        });
    </script>
</body>

</html>