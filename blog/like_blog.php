<?php
include("../config/db.php");

if (!isset($_SESSION['user_id']) || $_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['toggle_like'])) {
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

// Check if already liked
$stmt = $conn->prepare("SELECT id FROM likes WHERE user_id = ? AND blog_id = ?");
$stmt->bind_param("ii", $user_id, $blog_id);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    $stmt->close();
    $del = $conn->prepare("DELETE FROM likes WHERE user_id = ? AND blog_id = ?");
    $del->bind_param("ii", $user_id, $blog_id);
    $del->execute();
    $del->close();
} else {
    $stmt->close();
    $ins = $conn->prepare("INSERT INTO likes (user_id, blog_id) VALUES (?, ?)");
    $ins->bind_param("ii", $user_id, $blog_id);
    $ins->execute();
    $ins->close();
}

header("Location: view_blog.php?id=$blog_id");
exit;
