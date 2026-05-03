<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "blog_platform_db";

$conn = mysqli_connect($host, $user, $pass, $db);
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

// Set charset to prevent encoding-based SQL injection
mysqli_set_charset($conn, "utf8mb4");

session_start();

// ── DATABASE MIGRATION ──────────────────────────────────────────────
// Run this SQL once to add the credibility score column:
//
//   ALTER TABLE blogs
//     ADD COLUMN credibility_label VARCHAR(20) DEFAULT NULL,
//     ADD COLUMN credibility_score DECIMAL(5,2) DEFAULT NULL;
//
// credibility_label: 'TRUE', 'FAKE', or 'UNCERTAIN'
// credibility_score: confidence percentage (0.00 – 100.00)
// ─────────────────────────────────────────────────────────────────────

// ── CSRF Token Helpers ──────────────────────────────────────────────
function generate_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_input(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generate_csrf_token()) . '">';
}

function verify_csrf_token(): bool {
    if (empty($_POST['csrf_token']) || empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// ── Flash Message Helpers ───────────────────────────────────────────
function set_flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

// ── Auth Guard ──────────────────────────────────────────────────────
function require_login(): void {
    if (!isset($_SESSION['user_id'])) {
        set_flash('error', 'Please log in to continue.');
        header("Location: /ThinkPost/auth/login.php");
        exit;
    }
}

// ── Credibility Badge Helper ────────────────────────────────────────
function credibility_badge(?string $label, ?float $score): string {
    if (!$label) return '';

    $score_display = $score !== null ? round($score) . '%' : '';

    switch ($label) {
        case 'TRUE':
            return '<span class="badge badge-true" title="ML Credibility Score">✅ Verified ' . $score_display . '</span>';
        case 'FAKE':
            return '<span class="badge badge-fake" title="ML Credibility Score">🔴 Flagged ' . $score_display . '</span>';
        case 'UNCERTAIN':
            return '<span class="badge badge-uncertain" title="ML Credibility Score">⚠️ Uncertain ' . $score_display . '</span>';
        default:
            return '';
    }
}

// ── Pagination Helper ───────────────────────────────────────────────
function pagination_links(int $current_page, int $total_pages, string $base_url): string {
    if ($total_pages <= 1) return '';

    $html = '<div class="pagination">';

    // Previous
    if ($current_page > 1) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page - 1) . '" class="page-link">← Prev</a>';
    }

    // Page numbers
    for ($i = 1; $i <= $total_pages; $i++) {
        if ($i == $current_page) {
            $html .= '<span class="page-link page-active">' . $i . '</span>';
        } elseif (abs($i - $current_page) <= 2 || $i === 1 || $i === $total_pages) {
            $html .= '<a href="' . $base_url . '&page=' . $i . '" class="page-link">' . $i . '</a>';
        } elseif (abs($i - $current_page) === 3) {
            $html .= '<span class="page-dots">…</span>';
        }
    }

    // Next
    if ($current_page < $total_pages) {
        $html .= '<a href="' . $base_url . '&page=' . ($current_page + 1) . '" class="page-link">Next →</a>';
    }

    $html .= '</div>';
    return $html;
}
