<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

if (!isset($_SESSION['student_id'])) {
    header("Location: student_login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_post'])) {
    $content = $_POST['content'];
    $image = $_FILES['image'];

    if (empty($content)) {
        echo "Post content is required!";
        exit();
    }

    $owner_id = $_SESSION['student_id'];
    $postId = createPost($owner_id, $content, $image, $pdo);

    header("Location: forum.php");
    exit();
}

function createPost($owner_id, $content, $image, $pdo)
{
    $imagePath = null;
    if ($image && isset($image['tmp_name'])) {
        $imagePath = 'uploads/' . basename($image['name']);
        move_uploaded_file($image['tmp_name'], $imagePath);
    }

    $query = $pdo->prepare("INSERT INTO posts (owner_id, content, image, created_at) 
                            VALUES (?, ?, ?, NOW())");
    $query->execute([$owner_id, $content, $imagePath]);

    return $pdo->lastInsertId();
}

function createReply($comment_id, $user_id, $reply_content, $pdo)
{
    $query = $pdo->prepare("INSERT INTO replies (comment_id, user_id, reply_content, created_at) 
                            VALUES (?, ?, ?, NOW(), ?)");
    $query->execute([$comment_id, $user_id, $reply_content]);

    return $pdo->lastInsertId();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reply_content'], $_POST['comment_id'])) {
    $reply_content = $_POST['reply_content'];
    $comment_id = $_POST['comment_id'];
    $user_id = $_SESSION['session_id'];

    if (empty($reply_content)) {
        echo "Reply content is required!";
        exit();
    }

    $replyId = createReply($comment_id, $user_id, $reply_content, $course_id, $pdo);

    header("Location: forum.php");
    exit();
}

function getPostsWithCommentsAndReplies($pdo)
{
    $query = $pdo->prepare("SELECT
        p.*,
        s.username,
        i.name
    FROM
        posts p
    LEFT JOIN students s ON
        s.id = p.owner_id
    LEFT JOIN instructors i ON
        i.id = p.owner_id
    ORDER BY
        p.created_at
    DESC
        ");
    $query->execute();
    $posts = $query->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        $commentsQuery = $pdo->prepare("SELECT
            c.*,
            s.username,
            i.name
        FROM
            comments c
        LEFT JOIN students s ON
            s.id = c.owner_id
        LEFT JOIN instructors i ON
            i.id = c.owner_id
        WHERE
            post_id = ?
        ORDER BY
            c.created_at
        DESC");
        $commentsQuery->execute([$post['id']]);
        $comments = $commentsQuery->fetchAll(PDO::FETCH_ASSOC);

        foreach ($comments as &$comment) {
            $repliesQuery = $pdo->prepare("SELECT
                r.*,
                s.username,
                i.name
            FROM
                replies r
            LEFT JOIN students s ON
                s.id = r.user_id
            LEFT JOIN instructors i ON
                i.id = r.user_id
            WHERE
                comment_id = ?
            ORDER BY
                r.created_at
            DESC
                ");
            $repliesQuery->execute([$comment['comment_id']]);
            $comment['replies'] = $repliesQuery->fetchAll(PDO::FETCH_ASSOC);
        }

        $post['comments'] = $comments;
    }

    return $posts;
}
?>

<?php
$posts = getPostsWithCommentsAndReplies($pdo);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="./assets/theme.css">
    <link rel="stylesheet" type="text/css" href="student.css">
    <style>
        .post-image {
            max-width: 500px;
            height: auto;
            width: 100%;
            margin: 20px auto;
            object-fit: contain;
        }
        .comment {
            padding-bottom: 10px;
            margin-bottom: 10px;
            border-bottom: 1px solid #888888;
        }

        .post {
            border: 1px solid #888888;
            padding: 20px;
            -webkit-box-shadow: 0px 0px 43px -20px rgba(0, 0, 0, 0.75);
            -moz-box-shadow: 0px 0px 43px -20px rgba(0, 0, 0, 0.75);
            box-shadow: 0px 0px 43px -20px rgba(0, 0, 0, 0.75);
            border-radius: 20px;
        }

        .reply {
            margin-left: 20px;
            border-left: 2px solid #ccc;
            padding-left: 10px;
            margin-top: 10px;
        }

        .forum-con {
            display: flex;
            gap: 20px;
            flex-direction: column;
            margin-top: 50px;
        }

        .post-header,
        .comment-header,
        .reply-header {
            margin-bottom: 10px;
        }

        .post-header p,
        .comment-header p,
        .reply-header p {
            line-height: 1em;
        }

        .post-header p:first-of-type,
        .comment-header p:first-of-type,
        .reply-header p:first-of-type {
            font-size: 20px;
        }

        .post-header p:last-of-type,
        .comment-header p:last-of-type,
        .reply-header p:last-of-type {
            font-size: 12px;
        }

        .comment-form form,
        .reply-form form {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: center;
            margin-bottom: 20px;
        }

        .comment-form form textarea,
        .reply-form form textarea {
            width: 80%;
        }
    </style>
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
                if (isset($_SESSION['student_username'])) {
                    echo '<li><strong><a href="profile_students.php"><i class="fas fa-user-circle"></i> ' . htmlspecialchars($_SESSION['student_name']) . '</a></strong></li>';
                    echo '<li><a href="#" id="logout"><i class="fas fa-sign-out-alt"></i> Logout</a></li>';
                } else {
                    echo '<li><a href="index.php">Login</a></li>';
                }
                ?>
            </ul>
        </nav>
        <script type="text/javascript">
            document.getElementById("logout").addEventListener("click", function(event) {
                event.preventDefault();

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
                        window.location.href = 'index.php';
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
    <section id="forum">
        <div class="wrapper">
            <div class="forum-container">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="field-container">
                        <label for="content">Post Content:</label>
                        <textarea name="content" id="content" placeholder="Write your post here..." required></textarea>
                    </div>
                    <div class="field-container">
                        <label for="image">Post Image (optional):</label>
                        <input type="file" name="image" id="image" accept="image/*">
                    </div>

                    <div class="btn-container">
                        <button type="submit" name="create_post" class="btn-primary">Create Post</button>
                    </div>
                </form>
                <div class="forum-con">
                    <?php foreach ($posts as $post): ?>
                        <div class="post">
                            <div class="post-header">
                                <p><strong><?php echo htmlspecialchars($post['name'] ? $post['name'] : $post['username']); ?></strong></p>
                                <p><em>Posted on <?php echo date("F j, Y, g:i a", strtotime($post['created_at'])); ?></em></p>
                            </div>
                            <div class="post-content">
                                <p><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                                <?php if ($post['image']): ?>
                                    <img src="<?php echo htmlspecialchars($post['image']); ?>" alt="Post Image" class="post-image">
                                <?php endif; ?>
                            </div>
                            <div class="comments-section">
                                <?php if (empty($post['comments'])): ?>
                                    <p>No comments yet.</p>
                                <?php else: ?>
                                    <?php foreach ($post['comments'] as $comment): ?>
                                        <div class="comment">
                                            <div class="comment-header">
                                                <p><strong><?php echo htmlspecialchars($comment['username']); ?></strong></p>
                                                <p><em>Commented on <?php echo date("F j, Y, g:i a", strtotime($comment['created_at'])); ?></em></p>
                                            </div>
                                            <div class="comment-content">
                                                <p><?php echo nl2br(htmlspecialchars($comment['content'])); ?></p>
                                            </div>
                                            <button class="view-replies-btn" data-comment-id="<?php echo $comment['comment_id']; ?>">View Replies</button>
                                            <div class="replies-section" id="replies-section-<?php echo $comment['comment_id']; ?>" style="display: none;">
                                                <?php if (empty($comment['replies'])): ?>
                                                    <p>No replies yet.</p>
                                                <?php else: ?>
                                                    <?php foreach ($comment['replies'] as $reply): ?>
                                                        <div class="reply">
                                                            <div class="reply-header">
                                                                <p><strong><?php echo htmlspecialchars($reply['username']); ?></strong></p>
                                                                <p><em>Replied on <?php echo date("F j, Y, g:i a", strtotime($reply['created_at'])); ?></em></p>
                                                            </div>
                                                            <div class="reply-content">
                                                                <p><?php echo nl2br(htmlspecialchars($reply['reply_content'])); ?></p>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                                <div class="reply-form">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                        <textarea name="reply_content" placeholder="Write your reply..." required></textarea>
                                                        <button type="submit" class="btn-primary">Reply</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                <div class="comment-form">
                                    <form method="POST" action="post_comment.php">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <textarea name="content" placeholder="Write a comment..." required></textarea>
                                        <button type="submit" class="btn-primary">Comment</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <script type="text/javascript">
        document.addEventListener("DOMContentLoaded", function() {
            const viewRepliesButtons = document.querySelectorAll(".view-replies-btn");

            viewRepliesButtons.forEach(button => {
                button.addEventListener("click", function() {
                    const commentId = button.getAttribute("data-comment-id"),
                        repliesSection = document.getElementById("replies-section-" + commentId);

                    if (repliesSection.style.display === "none" || repliesSection.style.display === "") {
                        repliesSection.style.display = "block";
                        button.innerText = "Hide Replies";
                    } else {
                        repliesSection.style.display = "none";
                        button.innerText = "View Replies";
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>