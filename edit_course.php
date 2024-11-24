<?php
// Database connection setup
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

// Check if course_id is provided
if (!isset($_GET['course_id'])) {
    die("Course ID is required.");
}

$course_id = $_GET['course_id'];

// Fetch course data
$stmt = $pdo->prepare("SELECT * FROM courses WHERE id = ?");
$stmt->execute([$course_id]);
$course = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$course) {
    die("Course not found.");
}

// Fetch enrolled students
$students_stmt = $pdo->prepare("SELECT s.id AS student_id, s.name AS student_name FROM enrollments e
                                JOIN students s ON e.student_id = s.id
                                WHERE e.course_id = ?");
$students_stmt->execute([$course_id]);
$enrolled_students = $students_stmt->fetchAll(PDO::FETCH_ASSOC);

// Unenroll student if unenroll_student_id is set
if (isset($_GET['unenroll_student_id'])) {
    $unenroll_student_id = $_GET['unenroll_student_id'];
    $unenroll_stmt = $pdo->prepare("DELETE FROM enrollments WHERE student_id = ? AND course_id = ?");
    
    if ($unenroll_stmt->execute([$unenroll_student_id, $course_id])) {
        echo "<script>alert('Student unenrolled successfully!');</script>";
        header("Location: edit_course.php?course_id=$course_id");
        exit();
    } else {
        echo "<script>alert('Error unenrolling student.');</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_course'])) {
    $course_name = $_POST['course_name'];
    $course_description = $_POST['course_description'];

    // Update course details
    $update_stmt = $pdo->prepare("UPDATE courses SET course_name = ?, course_description = ? WHERE id = ?");
    $update_stmt->execute([$course_name, $course_description, $course_id]);

    // Handle module and video file uploads
    $target_dir = "uploads/";

    // Process each module file
    if (isset($_FILES['module_file']) && !empty($_FILES['module_file']['name'][0])) {
        foreach ($_FILES['module_file']['name'] as $key => $filename) {
            if ($_FILES['module_file']['error'][$key] == 0) {
                $target_file = $target_dir . basename($filename);
                move_uploaded_file($_FILES['module_file']['tmp_name'][$key], $target_file);
                
                $module_title = $_POST['module_title'][$key];
                $insert_stmt = $pdo->prepare("INSERT INTO modules (course_id, module_file, title) VALUES (?, ?, ?)");
                $insert_stmt->execute([$course_id, $target_file, $module_title]);
            }
        }
    }

    // Process each video file (no size limit)
    foreach ($_FILES['video_file']['name'] as $key => $filename) {
        if ($_FILES['video_file']['error'][$key] == 0) {
            $target_file = $target_dir . basename($filename);
            move_uploaded_file($_FILES['video_file']['tmp_name'][$key], $target_file);
            
            $video_title = $_POST['video_title'][$key];
            $insert_stmt = $pdo->prepare("INSERT INTO modules (course_id, video_file, title) VALUES (?, ?, ?)");
            $insert_stmt->execute([$course_id, $target_file, $video_title]);
        }
    }

    // Redirect with success query parameter
    header("Location: edit_course.php?course_id=$course_id&success=true");
    exit();
}



// Update module titles and files
if (isset($_POST['update_title'])) {
    foreach ($_POST['module_id'] as $index => $module_id) {
        $new_title = $_POST['module_title'][$index];
        $update_title_stmt = $pdo->prepare("UPDATE modules SET title = ? WHERE id = ?");
        $update_title_stmt->execute([$new_title, $module_id]);

        // Check and handle updated file
        if (!empty($_FILES['module_file_update']['name'][$index])) {
            $new_file = $_FILES['module_file_update']['name'][$index];
            $target_file = $target_dir . basename($new_file);
            move_uploaded_file($_FILES['module_file_update']['tmp_name'][$index], $target_file);

            $update_file_stmt = $pdo->prepare("UPDATE modules SET module_file = ? WHERE id = ?");
            $update_file_stmt->execute([$target_file, $module_id]);
        }
    }
}

// Delete a module if delete_module_id is set
if (isset($_GET['delete_module_id'])) {
    $module_id = $_GET['delete_module_id'];

    // Fetch module files before deletion
    $delete_stmt = $pdo->prepare("SELECT module_file, video_file FROM modules WHERE id = ?");
    $delete_stmt->execute([$module_id]);
    $module = $delete_stmt->fetch(PDO::FETCH_ASSOC);

    // Delete files from the server
    if ($module) {
        if ($module['module_file'] && file_exists($module['module_file'])) {
            unlink($module['module_file']);
        }
        if ($module['video_file'] && file_exists($module['video_file'])) {
            unlink($module['video_file']);
        }
    }

    // Delete the module from the database
    $delete_stmt = $pdo->prepare("DELETE FROM modules WHERE id = ?");
    if ($delete_stmt->execute([$module_id])) {
        echo "<script>alert('Module deleted successfully!');</script>";
    } else {
        echo "<script>alert('Error deleting module.');</script>";
    }
    
    // Redirect to avoid form resubmission prompt
    header("Location: edit_course.php?course_id=$course_id");
    exit();
}


// Fetch modules associated with the course
$modules = $pdo->prepare("SELECT * FROM modules WHERE course_id = ?");
$modules->execute([$course_id]);
$uploaded_modules = $modules->fetchAll(PDO::FETCH_ASSOC);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> <!-- Add SweetAlert -->

    <title>Edit Course</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px; }
        .container { display: flex; max-width: 1200px; margin: 0 auto; background-color: #ffffff; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); }
        .left-panel { width: 250px; margin-right: 30px; }
        .right-panel { width: 100%; }
        h2 { color: #333; }
        label { font-weight: bold; }
        input[type="text"], textarea, input[type="file"] { width: 100%; padding: 10px; margin: 5px 0 20px; border: 1px solid #ddd; border-radius: 5px; }
        button { padding: 10px 15px; background-color: #007bff; color: #ffffff; border: none; border-radius: 5px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #0056b3; }
        .close-btn { background-color: #dc3545; color: white; padding: 10px 15px; border: none; border-radius: 5px; cursor: pointer; }
        .close-btn:hover { background-color: #c82333; }
        .file-upload { margin-top: 20px; }
        .uploaded-files, .enrolled-students { margin-top: 30px; }
        ul { list-style-type: none; padding: 0; }
        li { padding: 10px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; }
        li:last-child { border-bottom: none; }
        .delete-btn { background-color: #dc3545; color: white; padding: 5px 10px; border: none; border-radius: 5px; cursor: pointer; }
        .delete-btn:hover { background-color: #c82333; }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            overflow: auto;
        }
        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
        }
        .modal-header {
            font-size: 1.5em;
            margin-bottom: 15px;
        }
        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Add space between buttons */
        .left-panel button {
            margin-bottom: 20px; /* Add space between buttons */
        }

        /* Table styling for uploaded modules inside modal */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        tr:hover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <!-- Button to trigger uploaded modules modal -->
            <button id="openModalBtn" class="button">Uploaded Modules</button>

            <!-- Button to trigger enrolled students modal -->
            <button id="openStudentsModalBtn" class="button">Enrolled Students</button>
        </div>

        <div class="right-panel">
<!-- Close button -->
<button class="close-btn" onclick="window.location.href = 'admin.php';">×</button>
            <h2>Edit Course</h2>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="course_id" value="<?php echo $course['id']; ?>">
                <label for="course_name">Course Name:</label>
                <input type="text" name="course_name" id="course_name" value="<?php echo htmlspecialchars($course['course_name']); ?>" required>
                <label for="course_description">Course Description:</label>
                <textarea name="course_description" id="course_description" rows="4" required><?php echo htmlspecialchars($course['course_description']); ?></textarea>

                <h3>Upload Modules</h3>
                <div class="file-upload">
                    <label>Module Files (PDF only):</label>
                    <input type="file" name="module_file[]" accept=".pdf" multiple>
                    <label>Module Titles:</label>
                    <input type="text" name="module_title[]" placeholder="Enter module title" >
                </div>
                <div class="file-upload">
                    <label>Video Files:</label>
                    <input type="file" name="video_file[]" accept=".mp4,.avi,.mov" multiple>
                    <label>Video Titles:</label>
                    <input type="text" name="video_title[]" placeholder="Enter video title" >
                </div>
                <button type="submit" name="update_course">Update Course</button>
            </form>
        </div>
    </div>
    <script>
    // Check if 'success' parameter is in the URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('success')) {
        // Display SweetAlert success modal
        Swal.fire({
            title: 'Success!',
            text: 'Course updated successfully!',
            icon: 'success',
            confirmButtonText: 'OK'
        });
    }
</script>


    <!-- Modal structure for uploaded modules -->
<!-- Modal structure for uploaded modules -->
<div id="uploadedModulesModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" id="closeModalBtn">&times;</span>
        <h3>Uploaded Modules</h3>
        <table class="modal-table">
            <thead>
                <tr>
                    <th>Module Title</th>
                    <th>File Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($uploaded_modules as $module): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($module['title']); ?></td>
                        <td>
                            <?php if (!empty($module['module_file'])): ?>
                                PDF
                            <?php elseif (!empty($module['video_file'])): ?>
                                Video
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($module['module_file'])): ?>
                                <button class="view-btn" onclick="window.open('<?php echo htmlspecialchars($module['module_file']); ?>', '_blank')">View</button>
                                <?php endif; ?>
                            <?php if (!empty($module['video_file'])): ?>
                                <button class="view-btn" onclick="window.open('<?php echo htmlspecialchars($module['video_file']); ?>', '_blank')">View</button>
                                <?php endif; ?>
                            <form action="edit_course.php?course_id=<?php echo $course_id; ?>&delete_module_id=<?php echo $module['id']; ?>" method="post" style="display:inline;">
                                <button class="delete-btn" type="submit">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal structure for enrolled students -->
<div id="enrolledStudentsModal" class="modal">
    <div class="modal-content">
        <span class="close-modal" id="closeStudentsModalBtn">&times;</span>
        <h3>Enrolled Students</h3>
        <table class="modal-table">
            <thead>
                <tr>
                    <th>Student Name</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($enrolled_students as $student): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($student['student_name']); ?></td>
                        <td>
                            <a href="edit_course.php?course_id=<?php echo $course_id; ?>&unenroll_student_id=<?php echo $student['student_id']; ?>" class="delete-btn">Unenroll</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add the following CSS to style the modal and table -->
<style>
    /* General modal styles */
    .view-btn {
    display: inline-block;
    padding: 8px 16px;
    background-color: #4CAF50;
    color: white;
    text-decoration: none;
    border-radius: 4px;
    font-size: 14px;
    text-align: center;
}

.view-btn:hover {
    background-color: darkgreen; /* Change color when hovered */
}

    .modal {
        display: none; 
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0, 0, 0, 0.4); 
        padding-top: 60px;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 900px;
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .close-modal {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close-modal:hover,
    .close-modal:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }

    /* Table styling */
    .modal-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }

    .modal-table th, .modal-table td {
        padding: 12px;
        text-align: left;
        border: 3px solid black;
    }

    .modal-table th {
        background-color: #f2f2f2;
    }

    .modal-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }

    /* Button styling */
   

 

    /* Close modal button */
    #closeModalBtn, #closeStudentsModalBtn {
        cursor: pointer;
    }
</style>



    <script>
        // Modal functionality for uploaded modules
        const modal = document.getElementById("uploadedModulesModal");
        const openModalBtn = document.getElementById("openModalBtn");
        const closeModalBtn = document.getElementById("closeModalBtn");

        openModalBtn.onclick = function() {
            modal.style.display = "block";
        }

        closeModalBtn.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        // Modal functionality for enrolled students
        const studentsModal = document.getElementById("enrolledStudentsModal");
        const openStudentsModalBtn = document.getElementById("openStudentsModalBtn");
        const closeStudentsModalBtn = document.getElementById("closeStudentsModalBtn");

        openStudentsModalBtn.onclick = function() {
            studentsModal.style.display = "block";
        }

        closeStudentsModalBtn.onclick = function() {
            studentsModal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target === studentsModal) {
                studentsModal.style.display = "none";
            }
        }
        
    </script>
</body>
</html>

