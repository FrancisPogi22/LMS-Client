<?php
session_start(); // Start the session
require 'getProgress.php';

// Database connection
$host = 'localhost';
$db_name = 'lms';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}

$course_id = $_GET['course_id'];
$student_id = $_SESSION['student_id']; // Replace with your session variable name

// Fetch course details along with instructor's email, profile picture, and gender
$course = $pdo->prepare("
    SELECT c.*, i.name AS instructor_name, i.email, i.profile_picture, i.gender
    FROM courses c 
    JOIN instructors i ON c.instructor_id = i.id 
    WHERE c.id = ?
");
$course->execute([$course_id]);
$course = $course->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Course not found.");
}


// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = $_POST['post_content'];
    $image = ''; // Handle image upload if needed

    // Check if an image was uploaded
    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        // Define upload directory and filename
        $upload_dir = 'uploads/';
        $image = $upload_dir . basename($_FILES['post_image']['name']);

        // Move uploaded file to the desired directory
        if (!move_uploaded_file($_FILES['post_image']['tmp_name'], $image)) {
            echo "Error uploading image.";
        }
    }

    // Insert post into the database
    $stmt = $pdo->prepare("INSERT INTO posts (course_id, student_id, content, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$course_id, $student_id, $content, $image]);
    header("Location: courses.php?course_id=$course_id"); // Redirect to avoid resubmission
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment_content = $_POST['comment_content'];
    $post_id = $_POST['post_id'];

    // Insert comment into the database
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $student_id, $comment_content]);
    header("Location: courses.php?course_id=$course_id"); // Redirect to avoid resubmission
    exit;
}

// Handle reply submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'])) {
    $reply_content = $_POST['reply_content'];
    $comment_id = $_POST['comment_id'];

    // Insert reply into the database
    $stmt = $pdo->prepare("INSERT INTO comment_replies (comment_id, student_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $student_id, $reply_content]);
    header("Location: courses.php?course_id=$course_id"); // Redirect to avoid resubmission
    exit;
}

// Fetch posts for the course (from all enrolled students)
$posts = $pdo->prepare("SELECT p.*, s.name as student_name FROM posts p JOIN students s ON p.owner_id = s.id WHERE p.course_id = ?");
$posts->execute([$course_id]);
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);

// Fetch comments for each post
$comments = [];
foreach ($posts as $post) {
    $post_id = $post['id'];
    $comment_stmt = $pdo->prepare("SELECT c.*, s.name as student_name FROM comments c JOIN students s ON c.student_id = s.id WHERE c.post_id = ?");
    $comment_stmt->execute([$post_id]);
    $comments[$post_id] = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch replies for each comment
    foreach ($comments[$post_id] as $key => $comment) {
        $comment_id = $comment['comment_id'];
        $reply_stmt = $pdo->prepare("SELECT r.*, s.name as student_name FROM comment_replies r JOIN students s ON r.student_id = s.id WHERE r.comment_id = ?");
        $reply_stmt->execute([$comment_id]);
        $comments[$post_id][$key]['replies'] = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Fetch modules for the course
$modules = $pdo->prepare("SELECT
    m.*,
    q.id AS quiz_id
FROM
    modules m
LEFT JOIN quiz q ON q.course_id = m.course_id
WHERE
    m.course_id = ?
");
$modules->execute([$course_id]);
$modules = $modules->fetchAll(PDO::FETCH_ASSOC);

// Fetch completed modules for the student
$completed_modules = $pdo->prepare("SELECT module_id FROM completed_modules WHERE student_id = ?");
$completed_modules->execute([$student_id]);
$completed_modules = $completed_modules->fetchAll(PDO::FETCH_COLUMN);

function getStudentProgress($student_id, $course_id, $pdo, $action)
{
    try {
        $fileColumn = ($action === "video") ? "video_file" : "module_file";
        $query = $pdo->prepare("
            SELECT
                m.id AS module_id,
                CASE WHEN cm.module_id IS NOT NULL THEN 1 ELSE 0 END AS is_completed
            FROM
                modules m
            LEFT JOIN completed_modules cm 
                ON m.id = cm.module_id AND cm.student_id = :studentId
            WHERE
                m.course_id = :courseId
                AND m.$fileColumn IS NOT NULL
                AND m.$fileColumn != ''
        ");

        $query->execute(['studentId' => $student_id, 'courseId' => $course_id]);
        $modules = $query->fetchAll(PDO::FETCH_ASSOC);
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
    <link rel="stylesheet" href="styles.css">
    <title><?php echo htmlspecialchars($course['course_name']); ?></title>
    <link rel="stylesheet" href="./css/courses.css">
    <style>
        .progress-bar {
            width: 100%;
            background-color: #f3f3f3;
            border-radius: 8px;
            margin-bottom: 20px;
            position: relative;
            height: 20px;
            /* Set the height for the progress bar */
        }

        .progress {
            height: 100%;
            background-color: #4caf50;
            /* Green color for progress */
            border-radius: 8px;
            transition: width 0.3s ease;
            /* Smooth transition for width change */
        }

        #progressContainerModules {
            justify-content: end;
            gap: 20px;
            align-items: center;
            margin-top: 20px;
            display: flex;
            width: 100%;
            margin-bottom: 20px;
        }
    </style>
    <link rel="stylesheet" href="./assets/theme.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


</head>

<div>
    <header
        class="header"
        style="background-image: url('<?php echo htmlspecialchars($course['course_image']); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
        <button class="back-button" onclick="window.location.href='profile_students.php'">←</button>
        <div class="course-details">
            <h2><?php echo htmlspecialchars($course['course_name']); ?></h2>
        </div>
    </header>
    <div class="tabs">
        <div class="tab active" onclick="openTab(event, 'overviewTab')">Overview</div>
        <div class="tab" onclick="openTab(event, 'forumTab')">Forum</div>
        <div class="tab" onclick="openTab(event, 'assessmentTab')">Assessment</div>
        <div class="tab" onclick="openTab(event, 'modules')">Modules</div>
        <div class="tab" onclick="openTab(event, 'certificateTab')">E-Certificates</div>
    </div>
    <script>
        // Function to handle tab switching
        function openTab(event, tabName) {
            // Get all elements with class="tab" and remove the "active" class
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Add "active" class to clicked tab
            event.currentTarget.classList.add('active');

            // Get all elements with class="tab-content" and hide them
            const tabContents = document.querySelectorAll('.tab-content');
            tabContents.forEach(tabContent => {
                tabContent.style.display = 'none';
            });

            // Show the selected tab content
            const activeTabContent = document.getElementById(tabName);
            if (activeTabContent) {
                activeTabContent.style.display = 'block';
            }
        }

        // Set the initial tab when the page is loaded
        document.addEventListener('DOMContentLoaded', function() {
            const activeTab = document.querySelector('.tab.active');
            const activeTabName = activeTab ? activeTab.textContent.trim().toLowerCase() + 'Tab' : 'overviewTab';

            // Display content of the active tab
            openTab({
                currentTarget: activeTab
            }, activeTabName);

            // Optionally, ensure the active tab is visible on refresh
            if (!activeTab) {
                document.querySelector('.tab').classList.add('active');
            }
        });
    </script>
    <!-- Display E-Certificates -->
    <div id="certificateTab" class="tab-content" style="display: none;">
        <h3 style="text-align: center; font-family: Arial, sans-serif; margin-bottom: 20px;">E-Certificates</h3>
        <?php
        // Fetch e-certificates for the current course and student
        $certificates = $pdo->prepare("
        SELECT * 
        FROM e_certificates 
        WHERE course_id = ? AND student_id = ?
    ");
        $certificates->execute([$course_id, $student_id]);
        $certificates = $certificates->fetchAll(PDO::FETCH_ASSOC);

        if (count($certificates) > 0): ?>
            <div class="certificates-container">
                <?php foreach ($certificates as $certificate): ?>
                    <div class="certificate-display">
                        <?php
                        $filePath = htmlspecialchars($certificate['certificate_path']);
                        $fileExtension = pathinfo($filePath, PATHINFO_EXTENSION);

                        // Display certificates based on file type
                        if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])): ?>
                            <img src="<?php echo $filePath; ?>" alt="Certificate" class="certificate-image">
                        <?php elseif ($fileExtension === 'pdf'): ?>
                            <iframe src="<?php echo $filePath; ?>" class="certificate-pdf"></iframe>
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                        <p class="certificate-date">Uploaded on: <?php echo htmlspecialchars($certificate['uploaded_at']); ?></p>
                        <!-- Download Button -->
                        <a href="<?php echo $filePath; ?>" download class="download-button">Download Certificate</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; font-size: 16px; color: #555;">No certificates available for this course.</p>
        <?php endif; ?>
    </div>
    <!-- Overview Tab -->
    <div id="overviewTab" class="tab-content active">
        <h3>Overview</h3>
        <p><strong>Description:</strong> <?php echo htmlspecialchars($course['course_description']); ?></p>
        <p><strong>Instructor:</strong> <?php echo htmlspecialchars($course['instructor_name'] ?? 'Not available'); ?></p>

        <div class="instructor-profile">
            <h4>Instructor Profile</h4>
            <?php if ($course['profile_picture']): ?>
                <img src="<?php echo htmlspecialchars($course['profile_picture']); ?>" alt="Instructor Profile Picture" class="instructor-image">
            <?php else: ?>
                <img src="default-profile.jpg" alt="Default Profile Picture" class="instructor-image">
            <?php endif; ?>
            <p><strong>Name:</strong> <?php echo htmlspecialchars($course['instructor_name']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($course['email']); ?></p>
            <p><strong>Gender:</strong> <?php echo htmlspecialchars($course['gender']); ?></p>
        </div>
    </div>
    <div id="modules" class="tab-content">
        <div class="tab-progress-container">
            <div id="progressContainerModules">
                <?php
                $progress = getQuizProgress($student_id, $course_id, $pdo);
                ?>
                <label for="progressBarModules" style="font-size: 14px; font-weight: bold; color: #333;">Course Progress:</label>
                <svg width="40" height="40" viewBox="0 0 36 36" class="circular-chart">
                    <path class="circle-background"
                        stroke="#f3f3f3" stroke-width="4" fill="none"
                        d="M18 2.0845 a 15.915 15.915 0 0 1 0 31.83 a 15.915 15.915 0 0 1 0 -31.83" />
                    <path class="circle-progress"
                        stroke="#4caf50" stroke-width="4" fill="none"
                        stroke-dasharray="<?php echo $progress['progress']; ?>, 100"
                        d="M18 2.0845 a 15.915 15.915 0 0 1 0 31.83 a 15.915 15.915 0 0 1 0 -31.83" />
                </svg>
                <?php
                if ($progress != 0) {
                    echo "<p>" . $progress['total_score'] . " / " . $progress['total_questions'] . "</p>";
                }
                ?>

            </div>
        </div>
        <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">Uploaded Modules (PDF)</h3>
        <div class="uploaded-modules" style="list-style-type: none; padding: 0; margin-top: 10px;">
            <?php if (empty($modules)): ?>
                <p>No PDF files available for this course yet.</p>
            <?php else: ?>
                <?php foreach ($modules as $module): ?>
                    <div class="module" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-bottom: 1px solid #ddd;">
                        <div class="module-box" style="border: 1px solid black; border-radius: 8px; padding: 10px; width: 100%; margin-bottom: 10px;">
                            <div class="module-title" style="font-size: 14px; font-weight: bold; color: #333; flex: 1;">
                                <?php echo htmlspecialchars($module['title']); ?>
                            </div><br>
                            <button onclick="showContent('<?php echo htmlspecialchars($module['title']); ?>',
                                '<?php if (!$module['module_file']) {
                                        echo 'video';
                                    } else {
                                        echo 'pdf';
                                    } ?>',
                                '<?php echo htmlspecialchars($module['module_file']); ?>')"
                                style="padding: 5px 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; white-space: nowrap;">
                                View Modules
                            </button>
                            <?php if (in_array($module['id'], $completed_modules)): ?>
                                <button class="completion-status" style="padding: 5px 8px; 
                                    background-color: #007bff; 
                                    color: white; 
                                    border: none; 
                                    border-radius: 4px; 
                                    cursor: not-allowed; 
                                    font-size: 12px;" disabled>Completed</button>
                            <?php else: ?>
                                <button type="button" class="completion-button" onclick="markAsComplete(<?php echo htmlspecialchars($module['id']); ?>, this)" class="completion-button" style="padding: 5px 8px; 
                                        background-color: #007bff; 
                                        color: white; 
                                        border: none; 
                                        border-radius: 4px; 
                                        cursor: pointer; 
                                        font-size: 12px;">Mark as Complete</button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php
        $quiz_id = isset($modules[0]['quiz_id']) ? $modules[0]['quiz_id'] : null;
        if ($quiz_id):
        ?>
            <a href="quiz.php?course_id=<?php echo $course_id; ?>&quiz_id=<?php echo $quiz_id; ?>" class="take-quiz-button" style="margin-top: 10px; display: inline-block; padding: 10px 15px; background-color: #4caf50; color: white; text-decoration: none; border-radius: 4px; font-size: 14px;">Take Quiz</a>
        <?php endif; ?>
    </div>

    <script>
        function markAsComplete(moduleId, button) {
            const studentId = <?php echo json_encode($student_id); ?>;

            button.disabled = true;
            button.textContent = "Processing...";

            fetch('complete_module.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        module_id: moduleId,
                        student_id: studentId,
                        course_id: <?php echo json_encode($course_id); ?>,
                    }),
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        button.textContent = "Completed";
                        button.classList.add('completion-status');
                        button.classList.remove('completion-button');
                        button.disabled = true;

                        const progress = data.progress;
                        const progressBar = document.querySelector('.circle-progress');
                        const progressLabel = document.querySelector('.progress-label');

                        if (progressBar) {
                            progressBar.style.strokeDasharray = `${progress}, 100`;
                        }

                        if (progressLabel) {
                            progressLabel.textContent = `${progress}%`;
                        }
                    } else {
                        button.textContent = "Mark as Complete";
                        button.disabled = false;
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    button.textContent = "Mark as Complete";
                    button.disabled = false;
                });
        }
    </script>

    <!-- PDF Modal -->
    <div id="pdfModal" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;">
        <div style="background: white; width: 80%; max-width: 900px; max-height: 85vh; overflow-y: auto; padding: 15px; position: relative;">
            <h2 id="pdfTitle" style="font-size: 18px; margin-bottom: 10px;"></h2>
            <iframe id="pdfViewer" src="" width="100%" height="600px" style="border: none;"></iframe>
            <button onclick="closePDFModal()" style="position: absolute; bottom: 10px; right: 15px; font-size: 16px; padding: 8px 16px; cursor: pointer; background-color: #f44336; color: white; border: none; border-radius: 4px;">
                Close PDF
            </button>
        </div>
    </div>

    <!-- Video Modal -->
    <div id="videoModal" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;">
        <div style="background: white; width: 80%; max-width: 900px; max-height: 85vh; overflow-y: auto; padding: 15px; position: relative;">
            <h2 id="videoTitle" style="font-size: 18px; margin-bottom: 10px;"></h2>
            <video id="videoPlayer" controls style="width: 100%; max-height: 80vh;">
                <source id="videoSource" src="" type="video/mp4">
                Your browser does not support the video tag.
            </video>
            <button onclick="closeVideoModal()" style="position: absolute; bottom: 10px; right: 15px; font-size: 16px; padding: 8px 16px; cursor: pointer; background-color: #f44336; color: white; border: none; border-radius: 4px;">
                Close Video
            </button>
        </div>
    </div>

    <script>
        // Function to toggle between Tabs
        function showTab(tabId) {
            const modulesTab = document.getElementById('modulesTab');
            const contentTab = document.getElementById('contentTab');

            if (tabId === 'modulesTab') {
                modulesTab.style.display = 'block';
                contentTab.style.display = 'none';
            } else if (tabId === 'contentTab') {
                modulesTab.style.display = 'none';
                contentTab.style.display = 'block';
            }
        }

        // Function to close PDF Modal
        function closePDFModal() {
            document.getElementById('pdfModal').style.display = 'none';
        }

        // Function to open PDF in Modal
        function viewPDF(file, title) {
            document.getElementById('pdfTitle').textContent = title;
            document.getElementById('pdfViewer').src = file;
            document.getElementById('pdfModal').style.display = 'flex';
        }

        // Function to close Video Modal
        function closeVideoModal() {
            document.getElementById('videoModal').style.display = 'none';
            const videoPlayer = document.getElementById('videoPlayer');
            videoPlayer.pause();
            videoPlayer.currentTime = 0; // Reset video player to the beginning
        }

        // Function to show Video content in modal
        function showVideo(videoFile, title) {
            if (!videoFile) {
                alert("Video file not found.");
                return;
            }

            document.getElementById('videoTitle').textContent = title;
            document.getElementById('videoSource').src = videoFile;
            document.getElementById('videoPlayer').load(); // Reload video
            document.getElementById('videoModal').style.display = 'flex'; // Show the video modal
        }

        // Initial Tab to show
        showTab('modulesTab');
    </script>




    <!-- JavaScript to Handle Modal Close -->

    <script>
        // Get student ID and course ID (replace with dynamic PHP variables)
        const studentId = <?php echo $student_id; ?>;
        const courseId = <?php echo $course_id; ?>;
        // Show PDF content in modal
        // Function to view the PDF in the modal
        function viewPDF(pdfFile, title) {
            if (!pdfFile) {
                alert("PDF file not found.");
                return;
            }

            // Set the title of the PDF modal
            document.getElementById('pdfTitle').textContent = title;
            // Set the source for the iframe to load the PDF
            document.getElementById('pdfViewer').src = pdfFile;

            // Display the modal
            document.getElementById('pdfModal').style.display = 'flex';
        }

        // Function to close the modals (PDF and video)
        function closeModal() {
            // Close both PDF and video modals
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('videoModal').style.display = 'none';

            // Reset the iframe source to stop loading the PDF
            document.getElementById('pdfViewer').src = '';

            // Reset the video player (if applicable)
            document.getElementById('videoPlayer').pause();
            document.getElementById('videoPlayer').currentTime = 0;
        }

        function showContent(title, type, file) {
            const modal = document.getElementById('modal');
            const modalContent = document.getElementById('modal-content');
            const modalTitle = document.getElementById('modal-title');

            modalTitle.textContent = title;

            if (type === 'pdf') {
                modalContent.innerHTML = `<iframe src="${file}" style="width: 100%; height: 900px; " frameborder="0"></iframe>`;
            } else if (type === 'video') {
                modalContent.innerHTML = `<video controls style="width: 100%; height: 800px;"><source src="${file}" type="video/mp4">Your browser does not support the video tag.</video>`;
            }

            modal.style.display = 'block'; // Show the modal
        }

        function closeModal(videoModal) {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-content').innerHTML = ''; // Clear content
        }

        function openTab(event, tabName) {
            const tabContent = document.querySelectorAll('.tab-content');
            const tabs = document.querySelectorAll('.tab');

            tabContent.forEach(tab => {
                tab.style.display = 'none';
            });
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            document.getElementById(tabName).style.display = 'block';
            event.currentTarget.classList.add('active');

            // Close the modal if it's open
            closeModal();
        }

        function togglePostForm() {
            const postForm = document.getElementById('postForm');
            if (postForm.style.display === 'none') {
                postForm.style.display = 'block'; // Show the form
            } else {
                postForm.style.display = 'none'; // Hide the form
            }
        }

        function showContent(title, type, file) {
            const modal = document.getElementById('modal');
            const videoModal = document.getElementById('videoModal');
            const modalContent = document.getElementById('modal-content');
            const modalTitle = document.getElementById('modal-title');
            if (type === 'pdf') {
                modalContent.innerHTML = `<iframe src="${file}" style="width: 100%; height: 900px;" frameborder="0"></iframe>`;
                modalTitle.textContent = title;
                modal.style.display = 'block';
            } else {
                const videoPlayer = document.getElementById('videoPlayer');
                const videoSource = videoPlayer.querySelector('source');
                videoSource.src = type;
                videoPlayer.load();
                videoModal.style.display = 'flex';
            }
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-content').innerHTML = '';
        }

        function openTab(event, tabName) {
            const tabContent = document.querySelectorAll('.tab-content');
            const tabs = document.querySelectorAll('.tab');
            tabContent.forEach(tab => tab.style.display = 'none');
            tabs.forEach(tab => tab.classList.remove('active'));
            document.getElementById(tabName).style.display = 'block';
            event.currentTarget.classList.add('active');
            closeModal();
        }
    </script>



    <!-- Forum Tab -->
    <div id="forumTab" class="tab-content">
        <h3>Forum</h3>

        <!-- Button to trigger the post form -->
        <button id="postButton" class="post-button" onclick="togglePostForm()">Post a new message</button>
        <br><br>

        <!-- Post Form (hidden by default) -->
        <div id="postForm" class="post-form" style="display: none; margin-top: 20px;">
            <form method="POST" enctype="multipart/form-data">
                <textarea name="post_content" rows="4" placeholder="What's on your mind?" required></textarea>
                <input type="file" name="post_image" accept="image/*">
                <div class="form-actions">
                    <button type="submit" class="btn-submit">Post</button>
                    <button type="button" class="btn-cancel" onclick="togglePostForm()">Cancel</button>
                </div>
            </form>
        </div>

        <!-- Inline Styling (Optional) -->
        <style>
            .btn-submit,
            .btn-cancel,
            .comment-form button {
                display: inline-block;
            }

            .comment-form button {
                background-color: #007BFF;
                /* Blue */
                color: white;
                border: none;
                padding: 8px 16px;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: background-color 0.3s ease-in-out;
                margin-top: 10px;
            }

            .comment-form button:hover {
                background-color: #0056b3;
                /* Darker Blue */
            }
        </style>

        <h4>Posts</h4>
        <?php if (empty($posts)): ?>
            <p>No posts yet.</p>
        <?php else: ?>
            <?php
            // Reverse the posts array so the latest posts are displayed first
            $posts = array_reverse($posts);
            ?>
            <?php foreach ($posts as $post): ?>
                <div class="forum-post">
                    <p><strong><?php echo htmlspecialchars($post['student_name']); ?></strong>
                        <span style="color: #888;">(<?php echo htmlspecialchars($post['created_at']); ?>)</span>
                    </p>
                    <p><?php echo htmlspecialchars($post['content']); ?></p>
                    <?php if ($post['image']): ?>
                        <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
                    <?php endif; ?>

                    <div class="comments">
                        <h5>Comments:</h5>
                        <?php if (isset($comments[$post['id']]) && !empty($comments[$post['id']])): ?>
                            <?php foreach ($comments[$post['id']] as $comment): ?>
                                <div class="comment">
                                    <p><strong><?php echo htmlspecialchars($comment['student_name']); ?></strong>
                                        <span style="color: #888;">(<?php echo htmlspecialchars($comment['created_at']); ?>)</span>
                                    </p>
                                    <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                        <?php endif; ?>
                    </div>

                    <!-- Comment Form -->
                    <form method="POST" class="comment-form">
                        <textarea name="comment_content" rows="2" placeholder="Add a comment..." required></textarea>
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit">Comment</button>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function togglePostForm() {
            var postForm = document.getElementById('postForm');
            postForm.style.display = postForm.style.display === 'none' ? 'block' : 'none';
        }
    </script>

    <div id="assessmentTab" class="tab-content">
        <h3>Assessments</h3>

        <?php
        $assessments = $pdo->prepare("
        SELECT a.*, i.name AS instructor_name 
        FROM assessments a
        JOIN instructors i ON a.instructor_id = i.id
        WHERE a.course_id = ?
    ");
        $assessments->execute([$course_id]);
        $assessments = $assessments->fetchAll(PDO::FETCH_ASSOC);

        // Check if assessments are available
        if (!empty($assessments)):
            foreach ($assessments as $assessment):
                $submission = $pdo->prepare("SELECT * FROM assessment_submissions WHERE assessment_id = ? AND student_id = ?");
                $submission->execute([$assessment['id'], $student_id]);
                $submission = $submission->fetch(PDO::FETCH_ASSOC);
        ?>
                <div class="assessment">
                    <p><strong>Assessment Title:</strong> <?php echo htmlspecialchars($assessment['assessment_title']); ?></p>
                    <p><strong>Instructor:</strong> <?php echo htmlspecialchars($assessment['instructor_name']); ?></p>
                    <p><strong>Assessment Description:</strong> <?php echo nl2br(htmlspecialchars($assessment['assessment_description'])); ?></p>
                    <p><em>Posted on: <?php echo date('F d, Y', strtotime($assessment['created_at'])); ?></em></p>
                    <?php if ($submission): ?>
                        <div class="feedback">
                            <h4>Your Submission</h4>
                            <p><?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?></p>
                            <p><em>Submitted on: <?php echo date('F d, Y', strtotime($submission['created_at'])); ?></em></p>
                        </div>
                    <?php else: ?>
                        <?php
                        if (isset($_POST['send_assessment'])) {
                            $course_id = $_GET['course_id'] ?? null;
                            $student_id =  $_SESSION['student_id'];
                            $assessment_id = $assessment['id'];
                            $result = handleAssignmentUpload($pdo, $course_id, $student_id, $assessment_id);
                            echo "<p>{$result}</p>";
                        }

                        ?>
                        <form method="POST" enctype="multipart/form-data">
                            <label for="post_file">Upload Your Assignment (PDF/DOC):</label>
                            <input type="text" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>" hidden>
                            <input type="file" name="post_file" id="post_file" accept=".docx, .pdf" required>
                            <button type="submit" name="send_assessment" class="btn-primary">Submit</button>
                        </form>
                    <?php endif; ?>
                    <div class="feedback">
                        <h4>Feedback</h4>
                        <?php
                        $feedbacks = $pdo->prepare("SELECT
                                af.id AS feedback_id,
                                af.comment AS feedback_comment,
                                af.created_at AS feedback_created_at,
                                i.name,
                                af.user_type
                            FROM
                                assessment_feedback af
                            JOIN assessment_submissions s ON
                                af.assessment_id = s.id
                            JOIN instructors i ON
                                af.user_id = i.id
                            WHERE
                                s.student_id = :student_id
                            ORDER BY
                                af.created_at
                            DESC
                                ");
                        $feedbacks->execute([':student_id' => $_SESSION['student_id']]);
                        $feedbacks = $feedbacks->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($feedbacks)):
                            foreach ($feedbacks as $feedback): ?>
                                <div class="feedback-item">
                                    <strong><?php echo htmlspecialchars($feedback['name']); ?> (<?php echo htmlspecialchars($feedback['user_type']); ?>):</strong>
                                    <p><?php echo nl2br(htmlspecialchars($feedback['feedback_comment'])); ?></p>
                                    <em><?php echo date('F d, Y', strtotime($feedback['feedback_created_at'])); ?></em>
                                </div>
                            <?php endforeach;
                        else: ?>
                            <p>No feedback yet.</p>
                        <?php endif;
                        ?>
                    </div>
                    <div class="student-comment">
                        <h4>Add Your Comment:</h4>
                        <form id="commentForm" method="POST" action="save_comment.php">
                            <input type="hidden" name="post_id" value="<?php echo htmlspecialchars($submission['id']); ?>"> <!-- 'id' refers to 'assessment_submission.id' -->
                            <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($submission['student_id']); ?>">

                            <textarea name="content" placeholder="Enter your comment" required rows="4"></textarea>
                            <button type="submit" class="submit-btn">Post Comment</button>
                        </form>
                    </div>

                    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

                    <script>
                        // Ensure the DOM is fully loaded before attaching event listeners
                        document.addEventListener("DOMContentLoaded", function() {
                            // Handle comment form submission
                            document.getElementById('commentForm').addEventListener('submit', function(event) {
                                event.preventDefault(); // Prevent the default form submission

                                const form = new FormData(this); // Get form data

                                // Send the data via AJAX to save_comment.php
                                fetch('save_comment.php', {
                                        method: 'POST',
                                        body: form
                                    })
                                    .then(response => response.json()) // Parse the JSON response
                                    .then(data => {
                                        if (data.status === 'success') {
                                            // SweetAlert for successful comment submission
                                            Swal.fire({
                                                title: 'Comment Posted!',
                                                text: data.message,
                                                icon: 'success',
                                                confirmButtonText: 'Okay'
                                            }).then(() => {
                                                // Optionally, you can reload the page or update the comment section dynamically
                                                location.reload(); // This will refresh the page to show the new comment
                                            });
                                        } else {
                                            // SweetAlert for error if comment posting fails
                                            Swal.fire({
                                                title: 'Error!',
                                                text: data.message,
                                                icon: 'error',
                                                confirmButtonText: 'Try Again'
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        // SweetAlert for any AJAX error
                                        Swal.fire({
                                            title: 'Error!',
                                            text: 'There was an issue with your comment submission. Please try again.',
                                            icon: 'error',
                                            confirmButtonText: 'Try Again'
                                        });
                                    });
                            });
                        });
                    </script>
                    <?php
                    if ($submission) {
                        $comments = $pdo->prepare("SELECT * FROM comments WHERE post_id = ?");
                        $comments->execute([$submission['id']]);
                        $comments = $comments->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($comments)): ?>
                            <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <br>
                                    <div class="comment-box">
                                        <strong>
                                            <?php
                                            // Check if the comment is from a student or instructor
                                            echo ($comment['student_id'] !== 0) ? "Student" : "Instructor";
                                            ?>:
                                        </strong>
                                        <p class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                        <em class="comment-time" style="font-size: 0.85em; color: #888;">
                                            <?php echo date('F d, Y', strtotime($comment['created_at'])); ?>
                                        </em>
                                    </div>


                                    <?php
                                    $replies = $pdo->prepare("SELECT * FROM replies WHERE comment_id = ?");
                                    $replies->execute([$comment['comment_id']]);
                                    $replies = $replies->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($replies)): ?>
                                        <?php foreach ($replies as $reply): ?>
                                            <div class="reply-item">
                                                <strong>
                                                    <?php echo ($reply['instructor_id'] !== 0) ? "Instructor (Reply)" : "Student (Reply)"; ?>:
                                                </strong>
                                                <p class="reply-content"><?php echo nl2br(htmlspecialchars($reply['reply_content'])); ?></p>
                                                <em class="reply-time">
                                                    <?php echo date('F d, Y', strtotime($reply['created_at'])); ?>
                                                </em>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No comments yet.</p>
                    <?php endif;
                    }
                    ?>
                </div>
            <?php endforeach;
        else: ?>
            <p>No assessments available for this course.</p>
        <?php endif; ?>

        <?php
        function handleAssignmentUpload($pdo, $course_id, $student_id, $assessment_id)
        {
            if (isset($_FILES['post_file']) && $_FILES['post_file']['error'] === UPLOAD_ERR_OK) {
                $fileTmpPath = $_FILES['post_file']['tmp_name'];
                $fileName = $_FILES['post_file']['name'];
                $fileSize = $_FILES['post_file']['size'];
                $fileType = $_FILES['post_file']['type'];

                $allowedFileTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
                $uploadDir = 'uploads/';

                if (!in_array($fileType, $allowedFileTypes)) {
                    return "Invalid file type. Only PDF and DOCX files are allowed.";
                }

                $newFileName = uniqid() . '-' . basename($fileName);

                if (move_uploaded_file($fileTmpPath, $uploadDir . $newFileName)) {
                    if (!$course_id || !$student_id || !$assessment_id) {
                        return "Missing required input data: course_id, student_id, or assessment_id.";
                    }

                    $stmt = $pdo->prepare("
                            INSERT INTO assessment_submissions 
                            (assessment_id, student_id, course_id, submission_text, created_at) 
                            VALUES (:assessment_id, :student_id, :course_id, :submission_text, NOW())
                        ");

                    if (!$stmt->execute([
                        ':assessment_id' => $assessment_id,
                        ':student_id' => $student_id,
                        ':course_id' => $course_id,
                        ':submission_text' => $newFileName,
                    ])) {
                        return "Failed to insert into database: " . implode(" | ", $stmt->errorInfo());
                    }

                    return "Assignment uploaded and saved successfully.";
                } else {
                    return "There was an error moving the uploaded file.";
                }
            } else {
                return "No file was uploaded or an error occurred.";
            }
        }
        ?>
    </div>
    <div id="modal" class="modal">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title"></h2>
        </div>
        <div class="modal-content" id="modal-content"></div>
    </div>
    <!-- Footer -->
    <footer style="background-color: #333; color: #fff; padding: 20px 0; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; margin-bottom: 10px;">
                <!-- About Section -->
                <div>
                    <h5>About Us</h5>
                    <p>We are dedicated to providing quality education and innovative solutions for students and instructors.</p>
                </div>

                <!-- Quick Links Section -->
                <div>
                    <h5>Quick Links</h5>
                    <ul style="list-style-type: none; padding: 0;">
                        <li><a href="home.php" style="color: #fff; text-decoration: none;">Home</a></li>
                        <li><a href="about.php" style="color: #fff; text-decoration: none;">About</a></li>
                        <li><a href="contact.php" style="color: #fff; text-decoration: none;">Contact</a></li>
                        <li><a href="terms.php" style="color: #fff; text-decoration: none;">Terms of Service</a></li>
                    </ul>
                </div>

                <!-- Social Media Section -->
                <div>
                    <h5>Follow Us</h5>
                    <ul style="list-style-type: none; padding: 0;">
                        <li><a href="#" style="color: #fff; text-decoration: none;">Facebook</a></li>
                        <li><a href="#" style="color: #fff; text-decoration: none;">Twitter</a></li>
                        <li><a href="#" style="color: #fff; text-decoration: none;">Instagram</a></li>
                        <li><a href="#" style="color: #fff; text-decoration: none;">LinkedIn</a></li>
                    </ul>
                </div>
            </div>

            <!-- Copyright Section -->
            <div style="border-top: 1px solid #555; padding-top: 15px; font-size: 14px;">
                <p>&copy; <?php echo date("Y"); ?> Your Company Name. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script>
        // Ensure the DOM is fully loaded before attaching event listeners
        document.addEventListener("DOMContentLoaded", function() {
            // Handle form submission
            document.getElementById('submissionForm').addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                const form = new FormData(this); // Get form data

                // Send the data via AJAX to submit_submissionas.php
                fetch('submit_submissionas.php', {
                        method: 'POST',
                        body: form
                    })
                    .then(response => response.json()) // Parse the JSON response
                    .then(data => {
                        if (data.status === 'success') {
                            // SweetAlert for successful submission
                            Swal.fire({
                                title: 'Submission Successful!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'Okay'
                            }).then(() => {
                                // Reload the page after successful submission
                                location.reload(); // This will refresh the page
                            });
                        } else {
                            // SweetAlert for error if submission fails
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'Try Again'
                            });
                        }
                    })
                    .catch(error => {
                        // SweetAlert for any AJAX error
                        Swal.fire({
                            title: 'Error!',
                            text: 'There was an issue with your submission. Please try again.',
                            icon: 'error',
                            confirmButtonText: 'Try Again'
                        });
                    });
            });
        });
    </script>











    <!-- Include modal script and other functionalities -->
    <script>
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        function highlightTab(event) {
            // Remove the 'active' class from all tabs
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            // Add the 'active' class to the clicked tab
            event.currentTarget.classList.add('active');
        }
    </script>

    </body>

</html>