<?php
// Include your database connection
include('db_connection.php');

// Start the session at the beginning
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
        // Make sure to move the uploaded file to the target directory
        if (move_uploaded_file($_FILES["instructor_profile_picture"]["tmp_name"], $target_file)) {
            // File upload successful
        } else {
            // Handle error for file upload failure
            echo "Sorry, there was an error uploading your file.";
            exit();
        }
    } else {
        // If no new file is uploaded, keep the existing profile picture in the database
        // Optionally, fetch the existing profile picture from the database
        $stmt = $pdo->prepare("SELECT profile_picture FROM instructors WHERE id = :instructor_id");
        $stmt->bindParam(':instructor_id', $instructor_id);
        $stmt->execute();
        $existing_picture = $stmt->fetchColumn();

        // Use the existing picture if no new one is uploaded
        $target_file = $existing_picture ?: NULL;
    }

    // Start a transaction to update both the instructors and courses tables
    $pdo->beginTransaction();

    try {
        // Prepare the SQL query to update the instructor
        $sql = "UPDATE instructors SET 
                name = :name,
                email = :email,
                gender = :gender,
                profile_picture = :profile_picture
                WHERE id = :instructor_id";

        // Prepare the statement
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':gender', $gender);
        $stmt->bindParam(':profile_picture', $target_file);
        $stmt->bindParam(':instructor_id', $instructor_id);

        // Execute the update for instructors
        $stmt->execute();

        // Prepare the SQL query to update the course assignment
        $sql_course = "UPDATE courses SET 
                       instructor_id = :instructor_id
                       WHERE id = :course_id";

        // Prepare and execute the course update
        $stmt_course = $pdo->prepare($sql_course);
        $stmt_course->bindParam(':instructor_id', $instructor_id);
        $stmt_course->bindParam(':course_id', $course_id);
        $stmt_course->execute();

        // Commit the transaction
        $pdo->commit();

        // Set a session flag to indicate that the update was successful
        $_SESSION['success_message'] = "Instructor details updated successfully!";

        // Redirect to the same page to avoid re-submission
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();

    } catch (Exception $e) {
        // If there's an error, roll back the transaction
        $pdo->rollBack();
        echo "Error: Could not update the instructor details. " . $e->getMessage();
    }
}

// Check if there's a success message and display it
if (isset($_SESSION['success_message'])) {
    echo "<script>
            alert('".$_SESSION['success_message']."');
            window.location.href = window.location.href; // Refresh the page
          </script>";
    unset($_SESSION['success_message']); // Remove the message after it's displayed
}
?>