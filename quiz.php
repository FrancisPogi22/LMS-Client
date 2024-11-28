<?php
session_start();
require 'db_connection.php';

if (!isset($_GET['course_id'])) {
    die('Course ID is missing.');
}

$student_id = $_SESSION['student_id'];
$quiz_id = $_GET['quiz_id'];
$course_id = $_GET['course_id'];

$query = $pdo->prepare("SELECT * FROM quiz WHERE course_id = ?");
$query->execute([$course_id]);
$quiz = $query->fetch(PDO::FETCH_ASSOC);

if (!$quiz) {
    die('No quiz found for this course.');
}

$query1 = $pdo->prepare("SELECT * FROM quiz_results WHERE student_id = ? AND quiz_id = ?");
$query1->execute([$student_id, $quiz_id]);
$quiz1 = $query1->fetch(PDO::FETCH_ASSOC);

// Fetch questions related to the quiz
$query = $pdo->prepare("SELECT * FROM questions WHERE quiz_id = ?");
$query->execute([$quiz['id']]);
$questions = $query->fetchAll(PDO::FETCH_ASSOC);

// Fetch options for each question
$options = [];
foreach ($questions as $question) {
    $query = $pdo->prepare("SELECT * FROM options WHERE question_id = ?");
    $query->execute([$question['id']]);
    $options[$question['id']] = $query->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($quiz['quiz_title']); ?></title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" href="./css/courses.css">
</head>

<body>
    <header
        class="header">
        <button class="back-button" onclick="window.location.href='profile_students.php'">←</button>
    </header>

    <section id="quiz">
        <div class="wrapper">
            <div class="quiz-container">
                <h1><?php echo htmlspecialchars($quiz['quiz_title']); ?></h1>

                <?php if ($quiz1): ?>
                    <p>You have already submitted this quiz. Your score was: <?php echo htmlspecialchars($quiz1['score']); ?></p>
                    <a href="courses.php?course_id=<?php echo $course_id ?>">Back to dashboard</a>
                <?php else: ?>
                    <form method="POST" action="quiz_result.php">
                        <input type="hidden" name="quiz_id" value="<?php echo $quiz['id']; ?>">
                        <?php foreach ($questions as $index => $question): ?>
                            <div class="question">
                                <p><strong><?php echo $index + 1; ?>. <?php echo htmlspecialchars($question['question_text']); ?></strong></p>
                                <?php foreach ($options[$question['id']] as $option): ?>
                                    <label>
                                        <input type="radio" name="answers[<?php echo $question['id']; ?>]" value="<?php echo $option['id']; ?>">
                                        <?php echo htmlspecialchars($option['option_text']); ?>
                                    </label><br>
                                <?php endforeach; ?>
                                <br>
                            </div>
                        <?php endforeach; ?>
                        <button type="submit" class="btn-primary">Submit Quiz</button>
                    </form>
                <?php endif; ?>

                <div id="result" style="margin-top: 20px; display: none;">
                    <h2>Your Score:</h2>
                    <p id="scoreText"></p>
                </div>
            </div>
        </div>
    </section>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);

            fetch('quiz_result.php', {
                    method: 'POST',
                    body: formData,
                })
                .then(response => response.text())
                .then(data => {
                    try {
                        const jsonData = JSON.parse(data);

                        if (jsonData.success) {
                            document.getElementById('scoreText').textContent = `Your score: ${jsonData.score} / ${jsonData.total}`;
                            document.getElementById('result').style.display = 'block';
                            document.querySelector('button[type="submit"]').style.display = 'none';

                            if (jsonData.message) {
                                const messageElement = document.createElement('p');
                                messageElement.textContent = jsonData.message;
                                document.getElementById('result').prepend(messageElement);
                            }

                            const backLink = document.createElement('a');
                            backLink.textContent = 'Back to dashboard';
                            backLink.classList.add('btn-primary');
                            backLink.href = `courses.php?course_id=<?php echo $course_id ?>`;
                            backLink.style.marginTop = '10px';
                            backLink.style.display = 'inline-block';
                            document.getElementById('result').prepend(backLink);
                        }
                    } catch (error) {
                        console.error('Error parsing JSON:', error);
                        document.getElementById('scoreText').textContent = 'Error parsing response. Please try again.';
                        document.getElementById('result').style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    document.getElementById('scoreText').textContent = 'Error submitting quiz. Please try again.';
                    document.getElementById('result').style.display = 'block';
                });
        });
    </script>
</body>

</html>