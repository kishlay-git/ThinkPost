<?php
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['post_comment'])) {
    header("Location: ../index.php");
    exit;
}

if (!verify_csrf_token()) {
    set_flash('error', 'Invalid request.');
    header("Location: ../index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$blog_id = (int)$_POST['blog_id'];
$comment = trim($_POST['comment']);

if (!empty($comment) && $blog_id > 0) {
    $stmt = $conn->prepare("INSERT INTO comments (user_id, blog_id, comment_text) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $user_id, $blog_id, $comment);
    $stmt->execute();
    $stmt->close();
}

header("Location: view_blog.php?id=$blog_id");
exit;
