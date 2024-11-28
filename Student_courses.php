<?php
// Database connection
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
            $_SESSION['enrollment_status'] = 'success'; // Set session flag for successful enrollment
        } else {
            error_log(print_r($stmt->errorInfo(), true));
            $_SESSION['enrollment_status'] = 'error'; // Set session flag for failed enrollment
        }
    } else {
        $_SESSION['enrollment_status'] = 'already_enrolled'; // Set session flag if already enrolled
    }

    // Redirect to prevent form re-submission on page refresh
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Include SweetAlert only if a session flag is set
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
if (isset($_SESSION['enrollment_status'])):
    $enrollmentStatus = $_SESSION['enrollment_status'];
    unset($_SESSION['enrollment_status']); // Unset the session variable after checking its value
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

// Fetch enrolled courses
$enrolled_courses = $pdo->prepare("SELECT c.* FROM courses c
                                    JOIN enrollments e ON c.id = e.course_id
                                    WHERE e.student_id = ?");
$enrolled_courses->execute([$student_id]);
$enrolled_courses = $enrolled_courses->fetchAll(PDO::FETCH_ASSOC);
// Function to get course progress
function getCourseProgress($pdo, $student_id, $course_id)
{
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM completed_modules WHERE student_id = ? AND module_id IN 
                            (SELECT id FROM modules WHERE course_id = ?)");
    $stmt->execute([$student_id, $course_id]);
    return $stmt->fetchColumn(); // Returns the number of completed modules for this course
}

// Get search term if exists
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';

// Modify query to include search condition
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11.7.0/dist/sweetalert2.min.css">
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
                <li><a href="forum.php">FORUM</a></li>
                <?php
                // Check if the user is logged in
                if (isset($_SESSION['student_username'])) {
                    // Display the registered username with a profile icon
                    echo '<li><strong><a href="profile_students.php"><i class="fas fa-user-circle"></i> ' . htmlspecialchars($_SESSION['student_name']) . '</a></strong></li>';
                    // Add a logout link with a logout icon and SweetAlert
                    echo '<li><a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>';
                } else {
                    // Display a login link if not logged in
                    echo '<li><a href="student_login.php">Login</a></li>';
                }
                ?>
            </ul>
        </nav>
        <!-- SweetAlert for logout confirmation -->
        <script type="text/javascript">
            document.getElementById("logout").addEventListener("click", function(event) {
                event.preventDefault(); // Prevent the default link behavior

                // Show SweetAlert confirmation dialog
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
                        // Redirect to the logout.php if the user confirms
                        window.location.href = 'student_login.php';
                    }
                });
            });
        </script>
        <style>
            .swal2-title {
                background-color: white;
            }

            #noResultsMessage {
                font-size: 18px;
                color: red;
                text-align: center;
                margin-top: 20px;
            }
        </style>
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
                        course.style.display = 'block'; // Show the course
                    } else {
                        course.style.display = 'none'; // Hide the course
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
            var searchInput = document.getElementById('searchInput').value.toLowerCase();
            var courses = document.querySelectorAll('.course');
            var noResultsMessage = document.getElementById('noResultsMessage');
            var noVisibleCourses = true; // Flag to track if any course is visible

            courses.forEach(function(course) {
                var courseName = course.querySelector('h3').textContent.toLowerCase();
                if (courseName.indexOf(searchInput) > -1) {
                    course.style.visibility = 'visible'; // Show course if it matches the search
                    noVisibleCourses = false; // At least one course matches
                } else {
                    course.style.visibility = 'hidden'; // Hide course but keep its space in the layout
                }
            });

            // If no course is visible, show the "no results" message
            if (noVisibleCourses) {
                if (!noResultsMessage) {
                    var messageElement = document.createElement('p');
                    messageElement.id = 'noResultsMessage';
                    messageElement.textContent = "There are no courses that match your search.";
                    document.body.appendChild(messageElement); // Append message to the page
                }
            } else {
                if (noResultsMessage) {
                    noResultsMessage.remove(); // Remove the message if there are visible courses
                }
            }
        }
    </script>



    <script>
        // Get modal element
        var modal = document.getElementById("descriptionModal");

        // Get modal description element
        var modalDescription = document.getElementById("modalDescription");
        var modalCourseId = document.getElementById("modalCourseId");

        // Get all course divs
        var courses = document.querySelectorAll(".course");

        // When the user clicks a course card, open the modal and set its content
        courses.forEach(function(course) {
            course.onclick = function() {
                var courseId = this.getAttribute('data-course-id');
                var courseDescription = this.getAttribute('data-course-description');
                modalDescription.textContent = courseDescription;
                modalCourseId.value = courseId; // Set the course ID for enrollment
                modal.style.display = "block"; // Show the modal
            };
        });

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        };

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>


    </main>

    <!-- Modal Structure -->
    <div id="descriptionModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h3>Course Description</h3>
            <div id="modalDescription" class="course-description"></div> <!-- Changed from <p> to <div> for styling -->
            <form method="POST" action="">
                <input type="hidden" name="course_id" id="modalCourseId">
                <input type="submit" name="enroll" value="Enroll" class="button">
            </form>
        </div>
    </div>
    <style>
    </style>
    <script>
        // Get modal element
        var modal = document.getElementById("descriptionModal");

        // Get modal description element
        var modalDescription = document.getElementById("modalDescription");
        var modalCourseId = document.getElementById("modalCourseId");

        // Get all course divs
        var courses = document.querySelectorAll(".course");

        // Variable to keep track of the currently selected course
        var selectedCourse = null;

        // When the user clicks a course card, open the modal, set its content, and highlight the selected course
        courses.forEach(function(course) {
            course.onclick = function() {
                // Remove highlight from previously selected course
                if (selectedCourse) {
                    selectedCourse.classList.remove("selected");
                }

                // Highlight the current course
                this.classList.add("selected");
                selectedCourse = this;

                // Set modal content
                var courseId = this.getAttribute('data-course-id');
                var courseDescription = this.getAttribute('data-course-description');
                modalDescription.textContent = courseDescription;
                modalCourseId.value = courseId; // Set the course ID for enrollment
                modal.style.display = "block"; // Show the modal
            };
        });

        // Get the <span> element that closes the modal
        var span = document.getElementsByClassName("close")[0];

        // When the user clicks on <span> (x), close the modal
        span.onclick = function() {
            modal.style.display = "none";
        };

        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        };
    </script>
</body>
<style>
    .course {
        cursor: pointer;
        border: 1px solid #ccc;
        padding: 10px;
        margin: 20px;
        background-color: #4169e1;
        border-radius: 5px;
        transition: transform 0.2s, background-color 0.2s;
        height: 50px;
    }

    .course:hover {
        background-color: #f0f0f0;
        transform: scale(1.02);
    }

    .course.selected {
        background-color: #d1ecf1;
        /* Light blue background */
        border: 2px solid #007bff;
        /* Blue border */
        transform: scale(1.03);
        /* Slightly larger for emphasis */
    }

    .course h3 {
        margin: 11;
        text-align: center;
    }
</style>

</html>