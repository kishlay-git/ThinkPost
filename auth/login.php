<?php
include("../config/db.php");

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    if (!verify_csrf_token()) {
        $error = "Invalid request. Please try again.";
    } else {
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, username, password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            if (password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent fixation
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: ../dashboard.php");
                exit;
            }
        }
        $error = "Invalid email or password.";
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("../includes/navbar.php"); ?>

    <div class="container">
        <h2>Welcome Back</h2>

        <?php if (!empty($error)): ?>
            <p class="alert alert-error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php echo csrf_input(); ?>

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   placeholder="you@example.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   placeholder="Your password">

            <button name="login">Log In</button>
        </form>

        <p class="auth-footer">
            New here? <a href="register.php">Create an account</a>
        </p>
    </div>
</body>

</html>
