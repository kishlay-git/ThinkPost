<?php
include("../config/db.php");

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    header("Location: ../index.php");
    exit;
}

// 1. Fetch Blog (including credibility fields)
$stmt = $conn->prepare(
    "SELECT blogs.*, users.username 
     FROM blogs JOIN users ON blogs.user_id = users.id 
     WHERE blogs.id = ?"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$blog = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$blog) {
    set_flash('error', 'Article not found.');
    header("Location: ../index.php");
    exit;
}

// 2. Like Count
$stmt = $conn->prepare("SELECT COUNT(*) AS count FROM likes WHERE blog_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$like_count = $stmt->get_result()->fetch_assoc()['count'];
$stmt->close();

// 3. Check if current user liked
$liked_by_me = false;
if (isset($_SESSION['user_id'])) {
    $uid  = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT 1 FROM likes WHERE user_id = ? AND blog_id = ?");
    $stmt->bind_param("ii", $uid, $id);
    $stmt->execute();
    $stmt->store_result();
    $liked_by_me = $stmt->num_rows > 0;
    $stmt->close();
}

// 4. Fetch Comments
$stmt = $conn->prepare(
    "SELECT comments.*, users.username 
     FROM comments 
     JOIN users ON comments.user_id = users.id 
     WHERE blog_id = ? 
     ORDER BY comments.created_at DESC"
);
$stmt->bind_param("i", $id);
$stmt->execute();
$comments = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($blog['title']); ?> — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("../includes/navbar.php"); ?>

    <div class="container">
        <div class="blog-card">
            <h1 class="blog-title view-title">
                <?php echo htmlspecialchars($blog['title']); ?>
            </h1>

            <div class="blog-meta" style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
                <span>
                    By <b><?php echo htmlspecialchars($blog['username']); ?></b> ·
                    <?php echo date("F j, Y, g:i a", strtotime($blog['created_at'])); ?>
                </span>
                <?php echo credibility_badge($blog['credibility_label'] ?? null, $blog['credibility_score'] ?? null); ?>
            </div>

            <hr>
            <div class="blog-content view-content">
                <?php echo nl2br(htmlspecialchars($blog['content'])); ?>
            </div>

            <div class="actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <form action="like_blog.php" method="POST" class="inline-form">
                        <?php echo csrf_input(); ?>
                        <input type="hidden" name="blog_id" value="<?php echo (int)$blog['id']; ?>">
                        <button name="toggle_like" class="<?php echo $liked_by_me ? 'btn-red' : 'btn'; ?> btn-icon">
                            <?php echo $liked_by_me ? '💔 Unlike' : '❤️ Like'; ?>
                        </button>
                    </form>
                <?php else: ?>
                    <a href="../auth/login.php" class="btn btn-view">Log in to Like</a>
                <?php endif; ?>

                <span class="like-count"><?php echo (int)$like_count; ?> Likes</span>
            </div>
        </div>

        <div class="comments-section">
            <h3>Comments</h3>

            <?php if (isset($_SESSION['user_id'])): ?>
                <form action="comment_blog.php" method="POST">
                    <?php echo csrf_input(); ?>
                    <input type="hidden" name="blog_id" value="<?php echo (int)$blog['id']; ?>">
                    <textarea name="comment" rows="3" placeholder="Write a comment…" required class="comment-box"></textarea>
                    <button name="post_comment">Post Comment</button>
                </form>
            <?php else: ?>
                <p class="empty-state"><a href="../auth/login.php">Log in</a> to leave a comment.</p>
            <?php endif; ?>

            <div class="comment-list">
                <?php if ($comments->num_rows === 0): ?>
                    <p class="empty-state">No comments yet. Be the first!</p>
                <?php endif; ?>

                <?php while ($c = $comments->fetch_assoc()): ?>
                    <div class="comment">
                        <strong><?php echo htmlspecialchars($c['username']); ?></strong>
                        <p><?php echo nl2br(htmlspecialchars($c['comment_text'])); ?></p>
                        <small class="comment-date"><?php echo date("M j, Y, g:i a", strtotime($c['created_at'])); ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>

</html>
<?php $stmt->close(); ?>
