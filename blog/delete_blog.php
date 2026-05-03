<?php
include("../config/db.php");
require_login();

$id      = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];

if ($id > 0) {
    $stmt = $conn->prepare("DELETE FROM blogs WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $id, $user_id);

    if ($stmt->execute() && $stmt->affected_rows > 0) {
        set_flash('success', 'Article deleted.');
    } else {
        set_flash('error', 'Could not delete the article.');
    }
    $stmt->close();
}

header("Location: ../dashboard.php");
exit;
