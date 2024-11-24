<?php
session_start();

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
$student_id = $_SESSION['student_id'];
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

    if (isset($_FILES['post_image']) && $_FILES['post_image']['error'] == 0) {
        $upload_dir = 'uploads/';
        $image = $upload_dir . basename($_FILES['post_image']['name']);

        if (!move_uploaded_file($_FILES['post_image']['tmp_name'], $image)) {
            echo "Error uploading image.";
        }
    }

    $stmt = $pdo->prepare("INSERT INTO posts (course_id, student_id, content, image) VALUES (?, ?, ?, ?)");
    $stmt->execute([$course_id, $student_id, $content, $image]);
    header("Location: courses.php?course_id=$course_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $comment_content = $_POST['comment_content'];
    $post_id = $_POST['post_id'];
    $stmt = $pdo->prepare("INSERT INTO comments (post_id, student_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$post_id, $student_id, $comment_content]);
    header("Location: courses.php?course_id=$course_id");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'])) {
    $reply_content = $_POST['reply_content'];
    $comment_id = $_POST['comment_id'];
    $stmt = $pdo->prepare("INSERT INTO comment_replies (comment_id, student_id, content) VALUES (?, ?, ?)");
    $stmt->execute([$comment_id, $student_id, $reply_content]);
    header("Location: courses.php?course_id=$course_id");
    exit;
}

$posts = $pdo->prepare("SELECT p.*, s.name as student_name FROM posts p JOIN students s ON p.student_id = s.id WHERE p.course_id = ?");
$posts->execute([$course_id]);
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);
$comments = [];

foreach ($posts as $post) {
    $post_id = $post['id'];
    $comment_stmt = $pdo->prepare("SELECT c.*, s.name as student_name FROM comments c JOIN students s ON c.student_id = s.id WHERE c.post_id = ?");
    $comment_stmt->execute([$post_id]);
    $comments[$post_id] = $comment_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($comments[$post_id] as $key => $comment) {
        $comment_id = $comment['comment_id'];
        $reply_stmt = $pdo->prepare("SELECT r.*, s.name as student_name FROM comment_replies r JOIN students s ON r.student_id = s.id WHERE r.comment_id = ?");
        $reply_stmt->execute([$comment_id]);
        $comments[$post_id][$key]['replies'] = $reply_stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
$modules->execute([$course_id]);
$modules = $modules->fetchAll(PDO::FETCH_ASSOC);
$completed_modules = $pdo->prepare("SELECT module_id FROM completed_modules WHERE student_id = ?");
$completed_modules->execute([$student_id]);
$completed_modules = $completed_modules->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['module_id'])) {
    $module_id = $_POST['module_id'];

    if (!in_array($module_id, $completed_modules)) {
        $insert = $pdo->prepare("INSERT INTO completed_modules (student_id, module_id) VALUES (?, ?)");
        $insert->execute([$student_id, $module_id]);
    }

    header("Location: courses.php?course_id=$course_id");
    exit;
}

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
    <link rel="stylesheet" href="./assets/theme.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <div class="tabs">
        <div class="tab active" onclick="openTab(event, 'overviewTab')">Overview</div>
        <div class="tab" onclick="openTab(event, 'contentTab')">Content</div>
        <div class="tab" onclick="openTab(event, 'modulesTab')">Modules</div>
        <div class="tab" onclick="openTab(event, 'forumTab')">Forum</div>
        <div class="tab" onclick="openTab(event, 'assessmentTab')">Assessment</div>
        <div class="tab" onclick="openTab(event, 'certificateTab')">E-Certificates</div>

    </div>
    <div id="certificateTab" class="tab-content" style="display: none;">
        <h3 style="text-align: center; font-family: Arial, sans-serif; margin-bottom: 20px;">E-Certificates</h3>
        <?php
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

                        if (in_array($fileExtension, ['jpg', 'jpeg', 'png'])): ?>
                            <img src="<?php echo $filePath; ?>" alt="Certificate" class="certificate-image">
                        <?php elseif ($fileExtension === 'pdf'): ?>
                            <iframe src="<?php echo $filePath; ?>" class="certificate-pdf"></iframe>
                        <?php else: ?>
                            <p>Unsupported file type.</p>
                        <?php endif; ?>
                        <p class="certificate-date">Uploaded on: <?php echo htmlspecialchars($certificate['uploaded_at']); ?></p>
                        <a href="<?php echo $filePath; ?>" download class="download-button">Download Certificate</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <p style="text-align: center; font-size: 16px; color: #555;">No certificates available for this course.</p>
        <?php endif; ?>
    </div>
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
    <div id="modulesTab" class="tab-content">
        <div id="progressContainerModules" style="margin-top: 20px; width: 100%; margin-bottom: 20px;">
            <?php
            $progress = getStudentProgress($student_id, $course_id, $pdo, "module");
            ?>
            <label for="progressBarModules" style="font-size: 14px; font-weight: bold; color: #333;">Course Progress:</label>
            <div style="background-color: #f3f3f3; width: 100%; border-radius: 5px; overflow: hidden;" class="progress-bar">
                <div id="progressBar" class="progress" style="height: 15px;width: <?php echo $progress; ?>%; background-color: #4caf50;"></div>
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
                                <?php if (in_array($module['id'], $completed_modules)): ?>
                                    <button class="completion-status" style="
                                        padding: 5px 8px; 
                                        background-color: #007bff; 
                                        color: white; 
                                        border: none; 
                                        border-radius: 4px; 
                                        cursor: not-allowed; 
                                        font-size: 12px;"
                                        disabled>
                                        Completed
                                    </button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($module['id']); ?>">
                                        <button type="submit" name="mark_done" class="completion-button" style="
                                            padding: 5px 8px; 
                                            background-color: #007bff; 
                                            color: white; 
                                            border: none; 
                                            border-radius: 4px; 
                                            cursor: pointer; 
                                            font-size: 12px;">
                                            Mark as Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div id="pdfModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0, 0, 0, 0.7); align-items: center; justify-content: center;">
            <div style="background: white; width: 80%; max-width: 900px; max-height: 85vh; overflow-y: auto; padding: 15px; position: relative;">
                <span onclick="closeModal()" style="position: absolute; top: 10px; right: 15px; font-size: 24px; cursor: pointer; color: #333;">&times;</span>

                <h2 id="pdfTitle" style="font-size: 18px; margin-bottom: 10px;"></h2>
                <iframe id="pdfViewer" src="" width="100%" height="600px"></iframe>
            </div>
        </div>
    </div>
    <div id="contentTab" class="tab-content">
        <div id="progressContainer" style="margin-top: 20px; width: 100%;">
            <?php
            $progress = getStudentProgress($student_id, $course_id, $pdo, "video");
            ?>
            <label for="progressBar" style="font-size: 14px; font-weight: bold; color: #333;">Course Progress:</label>
            <div style="background-color: #f3f3f3; width: 100%; border-radius: 5px; overflow: hidden;" class="progress-bar">
                <div id="progressBar" class="progress" style="height: 15px;width: <?php echo $progress; ?>%; background-color: #4caf50;"></div>
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
                                <?php if (in_array($module['id'], $completed_modules)): ?>
                                    <button class="completion-status" style="
                                        padding: 5px 8px; 
                                        background-color: #007bff; 
                                        color: white; 
                                        border: none; 
                                        border-radius: 4px; 
                                        cursor: not-allowed; 
                                        font-size: 12px;"
                                        disabled>
                                        Completed
                                    </button>
                                <?php else: ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="module_id" value="<?php echo htmlspecialchars($module['id']); ?>">
                                        <button type="submit" name="mark_done" class="completion-button" style="
                                            padding: 5px 8px; 
                                            background-color: #007bff; 
                                            color: white; 
                                            border: none; 
                                            border-radius: 4px; 
                                            cursor: pointer; 
                                            font-size: 12px;">
                                            Mark as Complete
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
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
        const studentId = <?php echo $student_id; ?>,
            courseId = <?php echo $course_id; ?>;

        function viewPDF(pdfFile, title) {
            if (!pdfFile) {
                alert("PDF file not found.");
                return;
            }

            document.getElementById('pdfTitle').textContent = title;
            document.getElementById('pdfViewer').src = pdfFile;
            document.getElementById('pdfModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('videoModal').style.display = 'none';
            document.getElementById('pdfViewer').src = '';
            document.getElementById('videoPlayer').pause();
            document.getElementById('videoPlayer').currentTime = 0;
        }

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
    </script>
    <div id="forumTab" class="tab-content">
        <h3>Forum</h3>
        <button id="postButton" class="post-button" onclick="togglePostForm()">Post a new message</button>
        <div id="postForm" class="post-form" style="display: none; margin-top: 20px;">
            <form method="POST" enctype="multipart/form-data">
                <textarea name="post_content" rows="4" placeholder="What's on your mind?" required></textarea>
                <input type="file" name="post_image" accept="image/*">
                <div class="form-actions">
                    <button type="submit">Post</button>
                    <button type="button" class="cancel-button" onclick="togglePostForm()">Cancel</button>
                </div>
            </form>
        </div>

        <h4>Posts</h4>
        <?php if (empty($posts)): ?>
            <p>No posts yet.</p>
        <?php else: ?>
            <?php
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

        if (!empty($assessments)):
            foreach ($assessments as $assessment):
                $submission = $pdo->prepare("SELECT * FROM assessment_submissions WHERE assessment_id = ? AND student_id = ?");
                $submission->execute([$assessment['id'], $student_id]);
                $submission = $submission->fetch(PDO::FETCH_ASSOC);

                print_r($student_id);
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
                        document.addEventListener("DOMContentLoaded", function() {
                            document.getElementById('commentForm').addEventListener('submit', function(event) {
                                event.preventDefault();

                                const form = new FormData(this);

                                fetch('save_comment.php', {
                                        method: 'POST',
                                        body: form
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.status === 'success') {
                                            Swal.fire({
                                                title: 'Comment Posted!',
                                                text: data.message,
                                                icon: 'success',
                                                confirmButtonText: 'Okay'
                                            }).then(() => {
                                                location.reload();
                                            });
                                        } else {
                                            Swal.fire({
                                                title: 'Error!',
                                                text: data.message,
                                                icon: 'error',
                                                confirmButtonText: 'Try Again'
                                            });
                                        }
                                    })
                                    .catch(error => {
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
                                    <?php
                                    $replies = $pdo->prepare("SELECT * FROM replies WHERE comment_id = ?");
                                    $replies->execute([$comment['comment_id']]);
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
    <div id="modal" class="modal">
        <div class="modal-header">
            <span class="close" onclick="closeModal()">&times;</span>
            <h2 id="modal-title"></h2>
        </div>
        <div class="modal-content" id="modal-content"></div>
    </div>
    <footer style="background-color: #333; color: #fff; padding: 20px 0; text-align: center;">
        <div style="max-width: 1200px; margin: 0 auto; padding: 0 15px;">
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; margin-bottom: 10px;">
                <div>
                    <h5>About Us</h5>
                    <p>We are dedicated to providing quality education and innovative solutions for students and instructors.</p>
                </div>
                <div>
                    <h5>Quick Links</h5>
                    <ul style="list-style-type: none; padding: 0;">
                        <li><a href="home.php" style="color: #fff; text-decoration: none;">Home</a></li>
                        <li><a href="about.php" style="color: #fff; text-decoration: none;">About</a></li>
                        <li><a href="contact.php" style="color: #fff; text-decoration: none;">Contact</a></li>
                        <li><a href="terms.php" style="color: #fff; text-decoration: none;">Terms of Service</a></li>
                    </ul>
                </div>
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
            <div style="border-top: 1px solid #555; padding-top: 15px; font-size: 14px;">
                <p>&copy; <?php echo date("Y"); ?> Your Company Name. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById('submissionForm').addEventListener('submit', function(event) {
                event.preventDefault();

                const form = new FormData(this);

                fetch('submit_submissionas.php', {
                        method: 'POST',
                        body: form
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                title: 'Submission Successful!',
                                text: data.message,
                                icon: 'success',
                                confirmButtonText: 'Okay'
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonText: 'Try Again'
                            });
                        }
                    })
                    .catch(error => {
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
        const studentId = <?php echo $student_id; ?>,
            courseId = <?php echo $course_id; ?>;

        function viewPDF(pdfFile, title) {
            if (!pdfFile) {
                alert("PDF file not found.");
                return;
            }

            document.getElementById('pdfTitle').textContent = title;
            document.getElementById('pdfViewer').src = pdfFile;
            document.getElementById('pdfModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('pdfModal').style.display = 'none';
            document.getElementById('videoModal').style.display = 'none';
            document.getElementById('pdfViewer').src = '';
            document.getElementById('videoPlayer').pause();
            document.getElementById('videoPlayer').currentTime = 0;
        }

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


        function markCompleted(event) {
            const button = event.target;
            const moduleId = button.getAttribute('data-module-id');
            let completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];

            if (button.textContent === 'Mark as Completed') {
                completedModules.push(moduleId);
                button.textContent = 'Completed';
            } else {
                completedModules = completedModules.filter(id => id !== moduleId);
                button.textContent = 'Mark as Completed';
            }

            localStorage.setItem(`completedModules_${studentId}_${courseId}`, JSON.stringify(completedModules));

            updateProgressBar();
            updateProgressBarModules();

            saveProgressToDatabase(moduleId, completedModules);
        }

        function updateProgressBar() {
            const completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];
            const totalModules = document.querySelectorAll('.module').length;
            const progress = (completedModules.length / totalModules) * 100;
            document.getElementById('progressBar').style.width = progress + '%';
        }

        function updateProgressBarModules() {
            const completedModules = JSON.parse(localStorage.getItem(`completedModules_${studentId}_${courseId}`)) || [];
            const totalModules = document.querySelectorAll('.module').length;
            const progress = (completedModules.length / totalModules) * 100;
            document.getElementById('progressBarModules').style.width = progress + '%';
        }

        function saveProgressToDatabase(moduleId, completedModules) {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', 'save_progress.php', true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log('Progress saved successfully!');
                }
            };
            xhr.send('student_id=' + studentId + '&course_id=' + courseId + '&completed_modules=' + JSON.stringify(completedModules));
        }
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

            modal.style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modal').style.display = 'none';
            document.getElementById('modal-content').innerHTML = '';
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

            closeModal();
        }

        function togglePostForm() {
            const postForm = document.getElementById('postForm');
            if (postForm.style.display === 'none') {
                postForm.style.display = 'block';
            } else {
                postForm.style.display = 'none';
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
    <script>
        function closeModal() {
            document.getElementById('modal').style.display = 'none';
        }

        function highlightTab(event) {
            const tabs = document.querySelectorAll('.tab');
            tabs.forEach(tab => tab.classList.remove('active'));

            event.currentTarget.classList.add('active');
        }
    </script>

</body>

</html>