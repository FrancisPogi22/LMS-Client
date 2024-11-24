

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.css" rel="stylesheet">

    <title>Document</title>
</head>
<body>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.4.24/dist/sweetalert2.min.js"></script>
 
<?php
// Include your database connection
include('db_connection.php');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the updated data from the form
    $instructor_id = $_POST['instructor_id'];
    $name = $_POST['instructor_name'];
    $email = $_POST['instructor_email'];
    $gender = $_POST['instructor_gender'];
    $course_id = $_POST['course_id'];

    // Handle profile picture upload if a new file is selected
    if (isset($_FILES['instructor_profile_picture']) && $_FILES['instructor_profile_picture']['error'] == 0) {
        $target_dir = "uploads/"; // Specify the directory to save uploaded files
        $target_file = $target_dir . basename($_FILES["instructor_profile_picture"]["name"]);
        // Move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["instructor_profile_picture"]["tmp_name"], $target_file)) {
            // File upload successful
        } else {
            // Handle error for file upload failure
            echo "Sorry, there was an error uploading your file.";
            exit();
        }
    } else {
        // If no new file is uploaded, keep the existing profile picture in the database
        $stmt = $pdo->prepare("SELECT profile_picture FROM instructors WHERE id = :instructor_id");
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->execute();
        $existing_picture = $stmt->fetchColumn();

        // Use the existing picture if no new one is uploaded
        $target_file = $existing_picture ?: NULL;
    }

    try {
        // Check if the selected course already has an assigned instructor
        $check_query = "SELECT instructor_id FROM courses WHERE id = :course_id";
        $check_stmt = $pdo->prepare($check_query);
        $check_stmt->bindParam(':course_id', $course_id);
        $check_stmt->execute();
        $existing_instructor_id = $check_stmt->fetchColumn();

        if ($existing_instructor_id && $existing_instructor_id != $instructor_id) {
            // Course is already assigned to a different instructor
            $_SESSION['error_message'] = "This course is already assigned to another instructor.";
            header("Location: admin.php");
            exit();
        }

        // Start a transaction to update both the instructors and courses tables
        $pdo->beginTransaction();

        // Update the instructor record
        $sql = "UPDATE instructors SET 
                name = :name,
                email = :email,
                gender = :gender,
                profile_picture = :profile_picture
                WHERE id = :instructor_id";

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':profile_picture', $target_file);
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->execute();

        // Assign the instructor to the course
        if ($course_id) {
            $sql_course = "UPDATE courses SET 
                           instructor_id = :instructor_id
                           WHERE id = :course_id";

            $stmt_course = $pdo->prepare($sql_course);
            $stmt_course->bindParam(':instructor_id', $instructor_id);
            $stmt_course->bindParam(':course_id', $course_id);
            $stmt_course->execute();
        }

        // Commit the transaction
        $pdo->commit();

        // Set success message
        $_SESSION['success_message'] = "Instructor successfully assigned to the course!";
        header("Location: admin.php");
        exit();

    } catch (Exception $e) {
        // If an error occurs, roll back the transaction
        $pdo->rollBack();
        $_SESSION['error_message'] = "Error updating instructor details: " . $e->getMessage();
        header("Location: admin.php");
        exit();
    }
}

// Handle SweetAlert messages for success and error
if (isset($_SESSION['error_message'])) {
    echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '".$_SESSION['error_message']."',
            }).then(function() {
                window.location.href = 'admin.php';
            });
          </script>";
    unset($_SESSION['error_message']);
}

if (isset($_SESSION['success_message'])) {
    echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: '".$_SESSION['success_message']."',
            }).then(function() {
                window.location.href = 'admin.php';
            });
          </script>";
    unset($_SESSION['success_message']);
}
?>
</body>
</html>