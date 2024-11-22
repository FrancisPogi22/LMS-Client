<?php
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

// Fetch posts with user name
$posts = $pdo->prepare("
    SELECT p.*, s.name 
    FROM posts p
    JOIN students s ON p.student_id = s.id
    ORDER BY p.created_at DESC
");
$posts->execute();
$posts = $posts->fetchAll(PDO::FETCH_ASSOC);

// Handle post submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_content'])) {
    $content = $_POST['post_content'];
    $student_id = 1; // Replace with session data as needed

    // Insert post into the database
    $insert = $pdo->prepare("INSERT INTO posts (student_id, content) VALUES (?, ?)");
    $insert->execute([$student_id, $content]);

    // Refresh the page to see the new post
    header("Location: forum_page.php");
    exit;
}

// Handle comment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['comment_content'])) {
    $post_id = $_POST['post_id'];
    $comment_content = $_POST['comment_content'];
    $student_id = 1; // Replace with session data as needed

    // Insert comment into the database
    $insertComment = $pdo->prepare("INSERT INTO comments (post_id, student_id, content) VALUES (?, ?, ?)");
    $insertComment->execute([$post_id, $student_id, $comment_content]);
    
    // Refresh the page to see the new comment
    header("Location: forum_page.php");
    exit;
}

// Fetch comments for each post
$comments = [];
foreach ($posts as $post) {
    $comment_query = $pdo->prepare("SELECT c.*, s.name AS student_name FROM comments c JOIN students s ON c.student_id = s.id WHERE c.post_id = ? ORDER BY c.created_at DESC");
    $comment_query->execute([$post['id']]);
    $comments[$post['id']] = $comment_query->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            padding: 0;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: auto;
            padding: 20px;
            background: white;
            border-radius: 5px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h1 {
            text-align: center;
            color: #333;
        }
        .post-form {
            margin-bottom: 20px;
        }
        .post {
            border-bottom: 1px solid #ccc;
            margin-bottom: 20px;
            padding-bottom: 10px;
        }
        .comments {
            margin-top: 10px;
            padding-left: 20px;
        }
        .comment {
            margin: 5px 0;
            padding: 5px;
            background-color: #e9e9e9;
            border-radius: 5px;
        }
        .comment-form {
            margin-top: 10px;
        }
        .btn {
            padding: 8px 12px;
            background-color: #4caf50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Forum</h1>

        <div class="post-form">
            <form method="POST">
                <textarea name="post_content" rows="4" required placeholder="What's on your mind?"></textarea>
                <br>
                <button type="submit" class="btn">Post</button>
            </form>
        </div>

        <?php foreach ($posts as $post): ?>
            <div class="post">
                <h4><?php echo htmlspecialchars($post['name']); ?> <small>(<?php echo htmlspecialchars($post['created_at']); ?>)</small></h4>
                <p><?php echo htmlspecialchars($post['content']); ?></p>

                <div class="comments">
                    <?php if (isset($comments[$post['id']])): ?>
                        <?php foreach ($comments[$post['id']] as $comment): ?>
                            <div class="comment">
                                <strong><?php echo htmlspecialchars($comment['student_name']); ?>:</strong>
                                <?php echo htmlspecialchars($comment['content']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <div class="comment-form">
                    <form method="POST">
                        <textarea name="comment_content" rows="2" required placeholder="Add a comment..."></textarea>
                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                        <button type="submit" class="btn">Comment</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</body>
</html>
c:\xampp\htdocs\GARRY_LMS\contents\student\first_page.php