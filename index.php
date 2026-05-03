<?php
include("config/db.php");

// ── Search & Pagination Config ──────────────────────────────────────
$search_query = trim($_GET['q'] ?? '');
$per_page     = 10;
$page         = max(1, (int)($_GET['page'] ?? 1));
$offset       = ($page - 1) * $per_page;

// ── Count total matching articles ───────────────────────────────────
if ($search_query !== '') {
    $like_term = '%' . $search_query . '%';
    $count_stmt = $conn->prepare(
        "SELECT COUNT(*) AS total FROM blogs WHERE title LIKE ? OR content LIKE ?"
    );
    $count_stmt->bind_param("ss", $like_term, $like_term);
} else {
    $count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM blogs");
}
$count_stmt->execute();
$total_posts = $count_stmt->get_result()->fetch_assoc()['total'];
$total_pages = max(1, ceil($total_posts / $per_page));
$count_stmt->close();

// ── Fetch articles for current page ─────────────────────────────────
if ($search_query !== '') {
    $stmt = $conn->prepare(
        "SELECT blogs.*, users.username, 
                blogs.credibility_label, blogs.credibility_score,
                (SELECT COUNT(*) FROM likes WHERE blog_id = blogs.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE blog_id = blogs.id) AS comment_count
         FROM blogs 
         JOIN users ON blogs.user_id = users.id 
         WHERE blogs.title LIKE ? OR blogs.content LIKE ?
         ORDER BY blogs.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ssii", $like_term, $like_term, $per_page, $offset);
} else {
    $stmt = $conn->prepare(
        "SELECT blogs.*, users.username, 
                blogs.credibility_label, blogs.credibility_score,
                (SELECT COUNT(*) FROM likes WHERE blog_id = blogs.id) AS like_count,
                (SELECT COUNT(*) FROM comments WHERE blog_id = blogs.id) AS comment_count
         FROM blogs 
         JOIN users ON blogs.user_id = users.id 
         ORDER BY blogs.created_at DESC
         LIMIT ? OFFSET ?"
    );
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$result = $stmt->get_result();

// Build base URL for pagination links
$base_url = "index.php?q=" . urlencode($search_query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ThinkPost — Public Feed</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("includes/navbar.php"); ?>

    <div class="container">
        <?php if ($search_query !== ''): ?>
            <h2>Search: "<?php echo htmlspecialchars($search_query); ?>"</h2>
            <p class="text-center text-muted">
                <?php echo $total_posts; ?> result<?php echo $total_posts !== 1 ? 's' : ''; ?> found
                · <a href="index.php">Clear search</a>
            </p>
        <?php else: ?>
            <h2>Public Feed</h2>
        <?php endif; ?>

        <?php if ($result->num_rows === 0): ?>
            <p class="empty-state">
                <?php echo $search_query ? 'No articles matched your search.' : 'No articles published yet. Be the first to write one!'; ?>
            </p>
        <?php endif; ?>

        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="blog-card">
                <div class="blog-title">
                    <?php echo htmlspecialchars($row['title']); ?>
                    <?php echo credibility_badge($row['credibility_label'], $row['credibility_score']); ?>
                </div>

                <div class="blog-meta">
                    By <b><?php echo htmlspecialchars($row['username']); ?></b> ·
                    <?php echo date("F j, Y", strtotime($row['created_at'])); ?>
                    · <?php echo (int)$row['comment_count']; ?> comments
                </div>

                <div class="blog-content">
                    <?php
                    $content = htmlspecialchars($row['content']);
                    echo (mb_strlen($content) > 160) ? mb_substr($content, 0, 160) . "…" : $content;
                    ?>
                </div>

                <div class="actions">
                    <span>❤️ <?php echo (int)$row['like_count']; ?> Likes</span>
                    <a href="blog/view_blog.php?id=<?php echo (int)$row['id']; ?>" class="btn">Read More →</a>
                </div>
            </div>
        <?php endwhile; ?>

        <?php echo pagination_links($page, $total_pages, $base_url); ?>
    </div>
</body>

</html>
<?php $stmt->close(); ?>
