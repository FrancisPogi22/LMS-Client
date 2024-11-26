<?php
session_start(); // Start the session

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
$posts = $pdo->prepare("SELECT p.*, s.name as student_name FROM posts p JOIN students s ON p.student_id = s.id WHERE p.course_id = ?");
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
$modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
$modules->execute([$course_id]);
$modules = $modules->fetchAll(PDO::FETCH_ASSOC);

// Fetch completed modules for the student
$completed_modules = $pdo->prepare("SELECT module_id FROM completed_modules WHERE student_id = ?");
$completed_modules->execute([$student_id]);
$completed_modules = $completed_modules->fetchAll(PDO::FETCH_COLUMN);

// Handle module completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    $module_id = $_POST['module_id'];

    // Check if the module is already marked as completed
    if (!in_array($module_id, $completed_modules)) {
        $insert = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
        $insert->execute([$student_id, $module_id]);
    }

    // Refresh the page to update progress bar and module completion status
    header("Location: course_details.php?course_id=$course_id");
    exit;
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
    
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

    <script>
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

        function closeModal() {
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
            const modalContent = document.getElementById('modal-content');
            const modalTitle = document.getElementById('modal-title');
            modalTitle.textContent = title;
            if (type === 'pdf') {
                modalContent.innerHTML = `<iframe src="${file}" style="width: 100%; height: 900px;" frameborder="0"></iframe>`;
            } else if (type === 'video') {
                modalContent.innerHTML = `<video controls style="width: 100%; height: 800px;"><source src="${file}" type="video/mp4">Your browser does not support the video tag.</video>`;
            }
            modal.style.display = 'block';
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
</head>
<body>
<header 
    class="header" 
    style="background-image: url('<?php echo htmlspecialchars($course['course_image']); ?>'); background-size: cover; background-position: center; background-repeat: no-repeat;">
    <button class="back-button" onclick="window.location.href='profile_students.php'">←</button>
    <div class="course-details">
        <h2><?php echo htmlspecialchars($course['course_name']); ?></h2>
    </div>
</header>
<style>
    .header {
    position: relative;
    height: 300px; /* Adjust based on your design */
    color: #fff; /* Text color for contrast */
    display: flex;
    align-items: center;
    padding: 20px;
}

.header .back-button {
    position: absolute;
    top: 20px;
    left: 20px;
    background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent button background */
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 5px;
    cursor: pointer;
}

.header .course-details {
    margin-left: auto;
    margin-right: auto;
    text-align: center;
}

.header h2 {
    font-size: 2rem;
    text-shadow: 0 2px 5px rgba(0, 0, 0, 0.7); /* Add text shadow for better readability */
    margin-top: 20vh;
    margin-right: 140vh;
}

</style>


    <div class="tabs">
        <div class="tab active" onclick="openTab(event, 'overviewTab')">Overview</div>
        <div class="tab" onclick="openTab(event, 'contentTab')">Content</div>
        <div class="tab" onclick="openTab(event, 'modulesTab')">Modules</div>
        <div class="tab" onclick="openTab(event, 'forumTab')">Forum</div>
        <div class="tab" onclick="openTab(event, 'assessmentTab')">Assessment</div>
    </div>
    <style>
.tabs {
    display: flex;
    margin-bottom: 20px;
}

.tab {
    padding: 10px 20px;
    cursor: pointer;
    background-color: #36454f; /* Default background color */
    border: 1px solid #ccc;
    margin-right: 5px;
    transition: background-color 0.3s ease;
    border-radius: 5px;
}

.tab.active {
    background-color: #ffffff; /* Highlight white background */
    color: #000; /* Optional: Change text color */
    font-weight: bold; /* Optional: Emphasize active tab */
}

    </style>
    <script>
       function highlightTab(event) {
    // Remove the 'active' class from all tabs
    const tabs = document.querySelectorAll('.tab');
    tabs.forEach(tab => tab.classList.remove('active'));

    // Add the 'active' class to the clicked tab
    event.currentTarget.classList.add('active');
}
 
    </script>

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

<style>
    #overviewTab {
        margin-top: 20px;
        padding: 15px;
        background-color: #f9f9f9; /* Optional: Add background color for better readability */
        border: 1px solid #ddd; /* Optional: Add a border to define the section */
        border-radius: 5px;
    }

    #overviewTab h3 {
        font-size: 1.8rem;
        margin-bottom: 15px; /* Add spacing below the heading */
    }

    #overviewTab p {
        font-size: 1rem;
        line-height: 1.5; /* Ensure proper spacing between lines */
        margin-bottom: 10px; /* Add spacing between paragraphs */
        word-wrap: break-word; /* Prevent long words from overflowing */
        overflow-wrap: break-word; /* Support for older browsers */
    }

    .instructor-profile {
        margin-top: 20px; /* Add space above the instructor profile section */
    }

    .instructor-profile h4 {
        font-size: 1.5rem;
        margin-bottom: 10px; /* Add spacing below the subheading */
    }

    .instructor-image {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        margin-bottom: 10px; /* Add spacing below the image */
        display: block; /* Ensure the image doesn't interfere with text alignment */
    }

    .instructor-profile p {
        margin-bottom: 10px; /* Space between each profile field */
    }
</style>

<!-- Modules Tab -->
<div id="modulesTab" class="tab-content">
    <!-- Course Progress Bar -->
    <div id="progressContainerModules" style="margin-top: 20px; width: 100%; margin-bottom: 20px;">
        <label for="progressBarModules" style="font-size: 14px; font-weight: bold; color: #333;">Course Progress:</label>
        <div style="background-color: #f3f3f3; width: 100%; border-radius: 5px; overflow: hidden;">
            <div id="progressBarModules" style="height: 15px; width: 0%; background-color: #4caf50;"></div>
        </div>
    </div>

    <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">Uploaded Modules (PDF)</h3>
    <div class="uploaded-modules" style="list-style-type: none; padding: 0; margin-top: 10px;">
        <?php if (empty($modules)): ?>
            <p>No PDF files available for this course yet.</p>
        <?php else: ?>
            <?php foreach ($modules as $module): ?>
                <?php if ($module['module_file']): ?>
                    <div class="module" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; border-bottom: 1px solid #ddd;">
                        <div class="module-box" style="border: 1px solid black; border-radius: 8px; padding: 10px; width: 100%; margin-bottom: 10px;">
                            <div class="module-title" style="font-size: 14px; font-weight: bold; color: #333; flex: 1;">
                                <?php echo htmlspecialchars($module['title']); ?>
                            </div><br>
                            <button onclick="viewPDF('<?php echo htmlspecialchars($module['module_file']); ?>', '<?php echo htmlspecialchars($module['title']); ?>')" style="padding: 5px 10px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; white-space: nowrap;">View Modules</button>
                            <!-- Completion Button -->
                            <button class="completion-button" data-module-id="<?php echo $module['id']; ?>" onclick="markCompleted(event)" style="padding: 5px 8px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 1600px;">
                                <?php echo in_array($module['id'], $completed_modules) ? 'Completed' : 'Mark as Completed'; ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- PDF Modal -->
    <div id="pdfModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;">
        <div style="background: white; width: 80%; max-width: 900px; max-height: 85vh; overflow-y: auto; padding: 15px; position: relative;">
            <!-- Close button for the PDF modal -->
            <span onclick="closeModal()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #333;">&times;</span>
            
            <h2 id="pdfTitle" style="font-size: 18px; margin-bottom: 10px;"></h2>
            <iframe id="pdfViewer" src="" width="100%" height="600px"></iframe>
        </div>
    </div>
</div>

<!-- Content Tab -->
<div id="contentTab" class="tab-content">
    <!-- Progress Bar -->
    <div id="progressContainer" style="margin-top: 20px; width: 100%;">
        <label for="progressBar" style="font-size: 14px; font-weight: bold; color: #333;">Course Progress:</label>
        <div style="background-color: #f3f3f3; width: 100%; border-radius: 5px; overflow: hidden;">
            <div id="progressBar" style="height: 15px; width: 0%; background-color: #4caf50;"></div>
        </div>
    </div>

    <h3 style="font-size: 18px; font-weight: bold; margin-bottom: 10px;">Uploaded Videos</h3>
    <div class="uploaded-modules" style="list-style-type: none; padding: 0; margin-top: 10px;">
        <?php if (empty($modules)): ?>
            <p>No videos available for this course yet.</p>
        <?php else: ?>
            <?php foreach ($modules as $index => $module): ?>
                <?php if ($module['video_file']): ?>
                    <div class="module" style="display: flex; align-items: center; justify-content: space-between; padding: 8px 12px; border-bottom: 1px solid #ddd;">
                        <div class="module-box" style="border: 1px solid black; border-radius: 8px; padding: 10px; width: 100%; margin-bottom: 10px;">
                            <div class="module-title" style="font-size: 14px; font-weight: bold; color: #333; flex: 1;"><?php echo htmlspecialchars($module['title']); ?></div><br>
                            <button onclick="showContent('<?php echo htmlspecialchars($module['title']); ?>', '<?php echo htmlspecialchars($module['video_file']); ?>')" style="padding: 5px 8px; background-color: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px;">View Video</button>
                            <!-- Completion Button -->
                            <button class="completion-button" data-module-id="<?php echo $module['id']; ?>" onclick="markCompleted(event)" style="padding: 5px 8px; background-color: #28a745; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 12px; margin-left: 1600px;">
                                <?php echo in_array($module['id'], $completed_modules) ? 'Completed' : 'Mark as Completed'; ?>
                            </button>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Video Modal -->
<div id="videoModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;">
    <div style="background: white; width: 80%; max-width: 900px; max-height: 85vh; overflow-y: auto; padding: 15px; position: relative;">
        <span onclick="closeModal()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer;">&times;</span>
        <h2 id="videoTitle" style="font-size: 18px; margin-bottom: 10px;"></h2>
        <video id="videoPlayer" controls style="width: 100%; height: auto; max-height: 80vh;">
            <source id="videoSource" src="" type="video/mp4">
            Your browser does not support the video tag.
        </video>
    </div>
</div>

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
// Show video content in modal
function showContent(title, videoFile) {
    if (!videoFile) {
        alert("Video file not found.");
        return;
    }

    document.getElementById('videoTitle').textContent = title;
    document.getElementById('videoSource').src = videoFile;
    document.getElementById('videoPlayer').load();
    document.getElementById('videoModal').style.display = 'flex';
}



// Initialize the completed modules from localStorage (if any)
document.addEventListener('DOMContentLoaded', function() {
    const completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];

    // Loop through all buttons and set their text accordingly
    const buttons = document.querySelectorAll('.completion-button');
    buttons.forEach(button => {
        const moduleId = button.getAttribute('data-module-id');
        if (completedModules.includes(moduleId)) {
            button.textContent = 'Completed';  // Change button text to 'Completed'
        }
    });

    updateProgressBar();
    updateProgressBarModules();
});

// Mark module as completed when button is clicked
function markCompleted(event) {
    const button = event.target;
    const moduleId = button.getAttribute('data-module-id');

    // Get the current list of completed modules from localStorage
    let completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];

    // If the button says 'Mark as Completed', mark it and update the button text
    if (button.textContent === 'Mark as Completed') {
        completedModules.push(moduleId);
        button.textContent = 'Completed';
    } else {
        completedModules = completedModules.filter(id => id !== moduleId);
        button.textContent = 'Mark as Completed';
    }

    // Save the updated list of completed modules to localStorage
    localStorage.setItem(`completedModules_${studentId}_${courseId}`, JSON.stringify(completedModules));
    
    // Update progress bar
    updateProgressBar();
    updateProgressBarModules();

    // Save progress to the database (AJAX request)
    saveProgressToDatabase(moduleId, completedModules);
}

// Update progress bar (Video)
function updateProgressBar() {
    const completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];
    const totalModules = document.querySelectorAll('.module').length;
    const progress = (completedModules.length / totalModules) * 100;
    document.getElementById('progressBar').style.width = progress + '%';
}

// Update progress bar (PDF)
function updateProgressBarModules() {
    const completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];
    const totalModules = document.querySelectorAll('.module').length;
    const progress = (completedModules.length / totalModules) * 100;
    document.getElementById('progressBarModules').style.width = progress + '%';
}

// Save progress to the database using AJAX
function saveProgressToDatabase(moduleId, completedModules) {
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'save_progress.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            // Handle the response (e.g., display a success message)
            console.log('Progress saved successfully!');
        }
    };
    xhr.send('student_id=' + studentId + '&course_id=' + courseId + '&completed_modules=' + JSON.stringify(completedModules));
}


</script>





   <!-- Forum Tab -->
<div id="forumTab" class="tab-content">
    <h3>Forum</h3>
    
    <!-- Button to trigger the post form -->
    <button id="postButton" class="post-button" onclick="togglePostForm()">Post a new message</button>

    <!-- Post Form (hidden by default) -->
    <div id="postForm" class="post-form" style="display: none; margin-top: 20px;">
        <form method="POST" enctype="multipart/form-data"> <!-- Added form tag and enctype -->
            <textarea name="post_content" rows="4" placeholder="What's on your mind?" required></textarea>
            <input type="file" name="post_image" accept="image/*">
            <div class="form-actions">
                <button type="submit">Post</button> <!-- Submit button for the form -->
                <button type="button" class="cancel-button" onclick="togglePostForm()">Cancel</button>
            </div>
        </form>
    </div>

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
                <p><strong><?php echo htmlspecialchars($post['student_name']); ?></strong> <span style="color: #888;">(<?php echo htmlspecialchars($post['created_at']); ?>)</span></p>
                <p><?php echo htmlspecialchars($post['content']); ?></p>
                <?php if ($post['image']): ?>
                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
                <?php endif; ?>
                
                <div class="comments">
                    <h5>Comments:</h5>
                    <?php if (isset($comments[$post['id']]) && !empty($comments[$post['id']])): ?>
                        <?php foreach ($comments[$post['id']] as $comment): ?>
                            <div class="comment">
                                <p><strong><?php echo htmlspecialchars($comment['student_name']); ?></strong> <span style="color: #888;">(<?php echo htmlspecialchars($comment['created_at']); ?>)</span></p>
                                <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                
                                <div class="replies">
                                    <?php if (!empty($comment['replies'])): ?>
                                        <h6>Replies:</h6>
                                        <?php foreach ($comment['replies'] as $reply): ?>
                                            <div class="reply">
                                                <p><strong><?php echo htmlspecialchars($reply['student_name']); ?></strong> <span style="color: #888;">(<?php echo htmlspecialchars($reply['created_at']); ?>)</span></p>
                                                <p><?php echo htmlspecialchars($reply['content']); ?></p>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    
                                    <!-- Reply Form -->
                                    <form method="POST" class="reply-form">
                                        <textarea name="reply_content" rows="2" placeholder="Add a reply..." required></textarea>
                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                        <button type="submit">Reply</button>
                                    </form>
                                </div>
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
    // Fetch assessments for the course
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
            // Fetch submission for the specific student for this assessment
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
                    <form id="submissionForm" method="POST" action="submit_submissionas.php">
        <input type="hidden" name="assessment_id" value="<?php echo htmlspecialchars($assessment['id']); ?>">
        <input type="hidden" name="student_id" value="<?php echo htmlspecialchars($student_id); ?>">
        <input type="hidden" name="course_id" value="<?php echo htmlspecialchars($course_id); ?>">
        <textarea name="submission_text" required placeholder="Write your submission here..."></textarea>
        <button type="submit">Submit</button>
    </form>
                <?php endif; ?>
                <!-- SweetAlert2 CDN -->



                <!-- Feedback Section -->
                <div class="feedback">
                    <h4>Feedback</h4>
                    <?php
                    if ($submission) { // Check if submission exists before querying feedback
                        $feedbacks = $pdo->prepare("SELECT f.*, 
                            CASE WHEN f.user_type = 'instructor' THEN i.name 
                                 WHEN f.user_type = 'student' THEN s.name 
                            END AS user_name
                            FROM assessment_feedback f
                            LEFT JOIN instructors i ON f.user_id = i.id AND f.user_type = 'instructor'
                            LEFT JOIN students s ON f.user_id = s.id AND f.user_type = 'student'
                            WHERE f.submission_id = ?");
                        $feedbacks->execute([$submission['id']]);
                        $feedbacks = $feedbacks->fetchAll(PDO::FETCH_ASSOC);

                        if (!empty($feedbacks)): 
                            foreach ($feedbacks as $feedback): ?>
                                <div class="feedback-item">
                                    <strong><?php echo htmlspecialchars($feedback['user_name']); ?> (<?php echo htmlspecialchars($feedback['user_type']); ?>):</strong>
                                    <p><?php echo nl2br(htmlspecialchars($feedback['comment'])); ?></p>
                                    <em><?php echo date('F d, Y', strtotime($feedback['created_at'])); ?></em>
                                </div>
                            <?php endforeach; 
                        else: ?>
                            <p>No feedback yet.</p>
                        <?php endif; 
                    }
                    ?>
                </div>

               <!-- Comment Section for Students -->
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



                <!-- Displaying Comments -->
                <?php
                if ($submission) {
                    // Fetch comments for the current submission (using the submission's 'id' as the 'post_id')
                    $comments = $pdo->prepare("SELECT * FROM comments WHERE post_id = ?");
                    $comments->execute([$submission['id']]);  // Use the submission 'id' as the 'post_id'
                    $comments = $comments->fetchAll(PDO::FETCH_ASSOC);

                    if (!empty($comments)):
                        foreach ($comments as $comment): ?>
                            <div class="comment-item">
                                <br>
                                <div class="comment-box">
                                <strong>You:</strong>
                                <p class="comment-content"><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                <em class="comment-time" style="font-size: 0.85em; color: #888;"><?php echo date('F d, Y', strtotime($comment['created_at'])); ?></em>
                            </div>
                                <?php echo date('F d, Y', strtotime($comment['created_at'])); ?></em>

                                <!-- Fetching and Displaying Instructor's Reply to the Comment -->
                                <?php
                                $replies = $pdo->prepare("SELECT * FROM replies WHERE comment_id = ?");
                                $replies->execute([$comment['comment_id']]);  // Use the correct comment_id for replies
                                $replies = $replies->fetchAll(PDO::FETCH_ASSOC);

                                if (!empty($replies)):
                                    foreach ($replies as $reply): ?>
                                        <div class="reply-item">
                                            <strong>Instructor (Reply):</strong>
                                            <p class="reply-content"><?php echo nl2br(htmlspecialchars($reply['reply_content'])); ?></p>
                                            <em class="reply-time"><?php echo date('F d, Y', strtotime($reply['created_at'])); ?></em>
                                        </div>
                                    <?php endforeach;
                                else: ?>
                                    <p>No replies yet.</p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach;
                    else: ?>
                        <p>No comments yet.</p>
                    <?php endif;
                }
                ?>
            </div>
        <?php endforeach; 
    else: ?>
        <p>No assessments available for this course.</p>
    <?php endif; ?>
</div>


<style>
    /* Ensure that the content does not overflow and wraps correctly */
.assessment p, .feedback p, .student-comment textarea {
    word-wrap: break-word; /* Ensure long words break to the next line */
    white-space: normal; /* Allow text to wrap within the container */
    overflow-wrap: break-word; /* Break long words that might overflow */
    margin-bottom: 1em;
}

/* To make textareas (for submissions and comments) responsive */
.student-comment textarea {
    width: 100%; /* Make the textarea full-width */
    height: auto; /* Adjust height dynamically */
    max-height: 200px; /* Limit the height of the textarea */
    box-sizing: border-box; /* Ensure padding does not affect width */
}

/* Optional: Add padding to make the content easier to read */
.assessment, .feedback, .student-comment {
    padding: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    background-color: #f9f9f9;
    margin-bottom: 20px;
}

.assessment h4, .feedback h4, .student-comment h4 {
    margin-top: 0;
}

.feedback-item, .comment-item, .reply-item {
    margin-bottom: 1em;
}

.comment-box, .reply-item {
    background-color: #f1f1f1;
    padding: 10px;
    border-radius: 5px;
}

.comment-time, .reply-time {
    font-size: 0.85em;
    color: #888;
}

</style>

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


</body>
</html>