<?php
include 'db_connection.php';
session_start();

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
            $_SESSION['enrollment_status'] = 'success';
        } else {
            error_log(print_r($stmt->errorInfo(), true));
            $_SESSION['enrollment_status'] = 'error';
        }
    } else {
        $_SESSION['enrollment_status'] = 'already_enrolled';
    }

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['enrollment_status'])):
    $enrollmentStatus = $_SESSION['enrollment_status'];
    unset($_SESSION['enrollment_status']);
?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            <?php if ($enrollmentStatus === 'success'): ?>
                Swal.fire({
                    title: 'Success!',
                    text: 'Enrolled successfully!',
                    icon: 'success',
                    confirmButtonText: 'OK'
                });
            <?php elseif ($enrollmentStatus === 'already_enrolled'): ?>
                Swal.fire({
                    title: 'Notice',
                    text: 'You are already enrolled in this course.',
                    icon: 'info',
                    confirmButtonText: 'OK'
                });
            <?php elseif ($enrollmentStatus === 'error'): ?>
                Swal.fire({
                    title: 'Error',
                    text: 'Enrollment failed. Please try again.',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
        });
    </script>
<?php
endif;

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

$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

if ($searchTerm) {
    $courses = $pdo->prepare("SELECT * FROM courses WHERE course_name LIKE ?");
    $courses->execute(["%$searchTerm%"]);
} else {
    $courses = $pdo->query("SELECT * FROM courses");
}


$courses = $courses->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.js"></script>
    <title>All Courses</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" type="text/css" href="student.css">
</head>

<body>
    <header>
        <div class="logo">
            <img src="./images/logo.png" alt="e-Journo Eskwela" />
        </div>
        <nav>
            <ul>
                <li><a href="about.php">ABOUT US</a></li>
                <?php
                if (isset($_SESSION['student_username'])) {
                    echo '<li><strong><a href="profile_students.php"><i class="fas fa-user-circle"></i> ' . htmlspecialchars($_SESSION['student_name']) . '</a></strong></li>';
                    echo '<li><a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>';
                } else {
                    echo '<li><a href="student_login.php">Login</a></li>';
                }
                ?>
            </ul>
        </nav>
        <script type="text/javascript">
            document.getElementById("logout").addEventListener("click", function(event) {
                event.preventDefault();

                Swal.fire({
                    title: 'Are you sure you want to logout?',
                    text: "You won't be able to revert this!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, logout',
                    cancelButtonText: 'No, cancel',
                    reverseButtons: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'student_login.php';
                    }
                });
            });
        </script>
    </header>
    <main>
        <h1>ALL COURSES</h1>
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search Courses" oninput="filterCourses()">
        </div>
        <script>
            function filterCourses() {
                const searchInput = document.getElementById('searchInput').value.toLowerCase();
                const courses = document.querySelectorAll('.course');

                courses.forEach(course => {
                    const courseName = course.getAttribute('data-course-name').toLowerCase();

                    if (courseName.includes(searchInput)) {
                        course.style.display = 'block';
                    } else {
                        course.style.display = 'none';
                    }
                });
            }
        </script>
        <div class="container" id="coursesContainer">

            <?php foreach ($courses as $course): ?>
                <div class="course" data-course-id="<?php echo $course['id']; ?>" data-course-description="<?php echo htmlspecialchars($course['course_description']); ?>">
                    <h3><?php echo htmlspecialchars($course['course_name']); ?></h3>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <script>
        function filterCourses() {
            let searchInput = document.getElementById('searchInput').value.toLowerCase(),
                courses = document.querySelectorAll('.course'),
                noResultsMessage = document.getElementById('noResultsMessage'),
                noVisibleCourses = true;

            courses.forEach(function(course) {
                var courseName = course.querySelector('h3').textContent.toLowerCase();
                if (courseName.indexOf(searchInput) > -1) {
                    course.style.visibility = 'visible';
                    noVisibleCourses = false;
                } else {
                    course.style.visibility = 'hidden';
                }
            });

            if (noVisibleCourses) {
                if (!noResultsMessage) {
                    var messageElement = document.createElement('p');
                    messageElement.id = 'noResultsMessage';
                    messageElement.textContent = "There are no courses that match your search.";
                    document.body.appendChild(messageElement);
                }
            } else {
                if (noResultsMessage) {
                    noResultsMessage.remove();
                }
            }
        }
    </script>

    <script>
        var modal = document.getElementById("descriptionModal"),
            modalDescription = document.getElementById("modalDescription"),
            modalCourseId = document.getElementById("modalCourseId"),
            courses = document.querySelectorAll(".course");

        courses.forEach(function(course) {
            course.onclick = function() {
                var courseId = this.getAttribute('data-course-id');
                var courseDescription = this.getAttribute('data-course-description');
                modalDescription.textContent = courseDescription;
                modalCourseId.value = courseId;
                modal.style.display = "block";
            };
        });

        var span = document.getElementsByClassName("close")[0];

        span.onclick = function() {
            modal.style.display = "none";
        };

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>
    </main>
    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Course Description</h3>
            <div id="modalDescription" class="course-description"></div>
            <form method="POST" action="">
                <input type="hidden" name="course_id" id="modalCourseId">
                <input type="submit" name="enroll" value="Enroll" class="button">
            </form>
        </div>
    </div>
    <script>
        var modal = document.getElementById("descriptionModal"),
            modalDescription = document.getElementById("modalDescription"),
            modalCourseId = document.getElementById("modalCourseId"),
            courses = document.querySelectorAll(".course"),
            selectedCourse = null;

        courses.forEach(function(course) {
            course.onclick = function() {
                if (selectedCourse) {
                    selectedCourse.classList.remove("selected");
                }

                this.classList.add("selected");
                selectedCourse = this;

                var courseId = this.getAttribute('data-course-id'),
                    courseDescription = this.getAttribute('data-course-description');
                modalDescription.textContent = courseDescription;
                modalCourseId.value = courseId;
                modal.style.display = "block";
            };
        });

        var span = document.getElementsByClassName("close")[0];

        span.onclick = function() {
            modal.style.display = "none";
        };

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>
</body>

</html>