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
    include('db_connection.php');
    session_start();

    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $instructor_id = $_POST['instructor_id'];
        $name = $_POST['instructor_name'];
        $email = $_POST['instructor_email'];
        $gender = $_POST['instructor_gender'];
        $course_id = $_POST['course_id'];

        if (isset($_FILES['instructor_profile_picture']) && $_FILES['instructor_profile_picture']['error'] == 0) {
            $target_dir = "uploads/";
            $target_file = $target_dir . basename($_FILES["instructor_profile_picture"]["name"]);
            if (move_uploaded_file($_FILES["instructor_profile_picture"]["tmp_name"], $target_file)) {
            } else {
                echo "Sorry, there was an error uploading your file.";
                exit();
            }
        } else {
            $stmt = $pdo->prepare("SELECT profile_picture FROM instructors WHERE id = :instructor_id");
            $stmt->bindParam(':instructor_id', $instructor_id);
            $stmt->execute();
            $existing_picture = $stmt->fetchColumn();
            $target_file = $existing_picture ?: NULL;
        }

        try {
            $check_query = "SELECT instructor_id FROM courses WHERE id = :course_id";
            $check_stmt = $pdo->prepare($check_query);
            $check_stmt->bindParam(':course_id', $course_id);
            $check_stmt->execute();
            $existing_instructor_id = $check_stmt->fetchColumn();

            if ($existing_instructor_id && $existing_instructor_id != $instructor_id) {
                $_SESSION['error_message'] = "This course is already assigned to another instructor.";
                header("Location: admin.php");
                exit();
            }

            $pdo->beginTransaction();
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
            if ($course_id) {
                $sql_course = "UPDATE courses SET 
                           instructor_id = :instructor_id
                           WHERE id = :course_id";

                $stmt_course = $pdo->prepare($sql_course);
                $stmt_course->bindParam(':instructor_id', $instructor_id);
                $stmt_course->bindParam(':course_id', $course_id);
                $stmt_course->execute();
            }
            $pdo->commit();
            $_SESSION['success_message'] = "Instructor successfully assigned to the course!";
            header("Location: admin.php");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $_SESSION['error_message'] = "Error updating instructor details: " . $e->getMessage();
            header("Location: admin.php");
            exit();
        }
    }

    if (isset($_SESSION['error_message'])) {
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '" . $_SESSION['error_message'] . "',
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
                text: '" . $_SESSION['success_message'] . "',
            }).then(function() {
                window.location.href = 'admin.php';
            });
          </script>";
        unset($_SESSION['success_message']);
    }
    ?>
</body>

</html>