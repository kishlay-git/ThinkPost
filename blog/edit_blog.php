<?php
include("../config/db.php");
require_login();

$id      = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

// Fetch blog owned by current user
$stmt = $conn->prepare("SELECT * FROM blogs WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$blog   = $result->fetch_assoc();
$stmt->close();

if (!$blog) {
    set_flash('error', 'Article not found or you do not have permission to edit it.');
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    if (!verify_csrf_token()) {
        $error = "Invalid request. Please try again.";
    } else {
        $title   = trim($_POST['title']);
        $content = trim($_POST['content']);

        if (empty($title) || empty($content)) {
            $error = "Title and content cannot be empty.";
        } else {
            $stmt = $conn->prepare("UPDATE blogs SET title = ?, content = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ssii", $title, $content, $id, $user_id);

            if ($stmt->execute()) {
                set_flash('success', 'Article updated successfully!');
                header("Location: ../dashboard.php");
                exit;
            } else {
                $error = "Failed to update. Please try again.";
            }
            $stmt->close();
        }

        // Update $blog with new values so form shows latest input
        $blog['title']   = $title;
        $blog['content'] = $content;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Article — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("../includes/navbar.php"); ?>

    <div class="container">
        <h2>Edit Article</h2>

        <?php if (!empty($error)): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post">
            <?php echo csrf_input(); ?>

            <label for="title">Title</label>
            <input type="text" id="title" name="title"
                   value="<?php echo htmlspecialchars($blog['title']); ?>" required>

            <label for="content">Content</label>
            <textarea id="content" name="content" rows="12" required><?php echo htmlspecialchars($blog['content']); ?></textarea>

            <button name="update">Save Changes</button>
            <a href="../dashboard.php" class="btn btn-cancel">Cancel</a>
        </form>
    </div>
</body>

</html>
