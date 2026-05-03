<?php
// Determine the active page from the current script path
$current_page = basename($_SERVER['SCRIPT_NAME'], '.php');
$current_dir  = basename(dirname($_SERVER['SCRIPT_NAME']));

function is_active(string $page, string $dir = ''): string {
    global $current_page, $current_dir;
    if ($dir && $current_dir === $dir) return ' active';
    if ($current_page === $page) return ' active';
    return '';
}
?>
<nav>
    <div class="nav-container">
        <a href="/ThinkPost/index.php" class="nav-brand">ThinkPost</a>

        <!-- Search Bar -->
        <form action="/ThinkPost/index.php" method="GET" class="nav-search inline-form">
            <input type="text" name="q" placeholder="Search articles…"
                   value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>"
                   class="search-input">
        </form>

        <div class="nav-links">
            <a href="/ThinkPost/index.php" class="<?php echo is_active('index'); ?>">Home</a>
            <a href="/ThinkPost/fact_check.php" class="<?php echo is_active('fact_check'); ?>">Fact Check</a>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="/ThinkPost/dashboard.php" class="<?php echo is_active('dashboard'); ?>">Dashboard</a>
                <a href="/ThinkPost/blog/add_blog.php" class="<?php echo is_active('add_blog'); ?>">Write</a>
                <a href="/ThinkPost/auth/logout.php" class="logout-btn">Logout</a>
            <?php else: ?>
                <a href="/ThinkPost/auth/login.php" class="auth-link<?php echo is_active('login'); ?>">Log In</a>
                <a href="/ThinkPost/auth/register.php" class="auth-link<?php echo is_active('register'); ?>">Sign Up</a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<?php
// Render flash messages
$flash = get_flash();
if ($flash): ?>
    <div class="container">
        <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    </div>
<?php endif; ?>
