<?php
include("config/db.php");
require_login();

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare(
    "SELECT blogs.*, 
            blogs.credibility_label, blogs.credibility_score,
            (SELECT COUNT(*) FROM likes WHERE blog_id = blogs.id) AS like_count,
            (SELECT COUNT(*) FROM comments WHERE blog_id = blogs.id) AS comment_count
     FROM blogs 
     WHERE user_id = ? 
     ORDER BY created_at DESC"
);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$total_posts = $result->num_rows;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("includes/navbar.php"); ?>

    <div class="container">
        <h2>My Dashboard</h2>
        <p class="text-center">
            Welcome back, <b><?php echo htmlspecialchars($_SESSION['username']); ?></b> —
            you have <?php echo $total_posts; ?> published <?php echo $total_posts === 1 ? 'article' : 'articles'; ?>.
        </p>
        <hr>

        <div class="text-center mb-3">
            <a href="blog/add_blog.php" class="btn btn-create">Write New Article</a>
        </div>

        <?php if ($total_posts === 0): ?>
            <p class="empty-state">You haven't published anything yet. Start writing!</p>
        <?php endif; ?>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="blog-card">
                <div class="blog-title">
                    <?php echo htmlspecialchars($row['title']); ?>
                    <?php echo credibility_badge($row['credibility_label'], $row['credibility_score']); ?>
                </div>

                <div class="blog-meta">
                    <?php echo date("F j, Y", strtotime($row['created_at'])); ?>
                    · <?php echo (int)$row['comment_count']; ?> comments
                </div>

                <div class="blog-content">
                    <?php
                    $content = htmlspecialchars($row['content']);
                    echo (mb_strlen($content) > 120) ? mb_substr($content, 0, 120) . "…" : $content;
                    ?>
                </div>

                <div class="actions">
                    <span>❤️ <?php echo (int)$row['like_count']; ?> Likes</span>

                    <a href="blog/view_blog.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-view">View</a>
                    <a href="blog/edit_blog.php?id=<?php echo (int)$row['id']; ?>" class="btn btn-warning">Edit</a>
                    <a href="blog/delete_blog.php?id=<?php echo (int)$row['id']; ?>"
                       onclick="return confirm('Delete this post and all its likes/comments?');"
                       class="btn btn-red">Delete</a>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</body>

</html>
<?php $stmt->close(); ?>
