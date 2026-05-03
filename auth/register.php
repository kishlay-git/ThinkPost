<?php
include("../config/db.php");

if (isset($_SESSION['user_id'])) {
    header("Location: ../dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    if (!verify_csrf_token()) {
        $errors[] = "Invalid request. Please try again.";
    } else {
        $username = trim($_POST['username']);
        $email    = trim($_POST['email']);
        $password = $_POST['password'];

        // Validation
        if (strlen($username) < 3 || strlen($username) > 30) {
            $errors[] = "Username must be 3–30 characters.";
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        if (strlen($password) < 8) {
            $errors[] = "Password must be at least 8 characters.";
        }

        // Check duplicate email
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "This email is already registered.";
            }
            $stmt->close();
        }

        // Check duplicate username
        if (empty($errors)) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $errors[] = "This username is already taken.";
            }
            $stmt->close();
        }

        // Register
        if (empty($errors)) {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $stmt   = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $email, $hashed);

            if ($stmt->execute()) {
                set_flash('success', 'Account created! Please log in.');
                header("Location: login.php");
                exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ThinkPost</title>
    <link rel="stylesheet" href="/ThinkPost/draw.css">
</head>

<body>
    <?php include("../includes/navbar.php"); ?>

    <div class="container">
        <h2>Create Account</h2>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars(implode(' ', $errors)); ?>
            </div>
        <?php endif; ?>

        <form method="post" novalidate>
            <?php echo csrf_input(); ?>

            <label for="username">Username</label>
            <input type="text" id="username" name="username" required
                   minlength="3" maxlength="30"
                   placeholder="Choose a username"
                   value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">

            <label for="email">Email</label>
            <input type="email" id="email" name="email" required
                   placeholder="you@example.com"
                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">

            <label for="password">Password</label>
            <input type="password" id="password" name="password" required
                   minlength="8"
                   placeholder="At least 8 characters">

            <button name="register">Create Account</button>
        </form>

        <p class="auth-footer">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </div>
</body>

</html>
