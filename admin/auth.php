<?php
/**
 * Admin session handling: login, logout, CSRF tokens.
 *
 * Include this at the top of every admin page. Calling require_login() below
 * the include redirects anonymous visitors to the login form.
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/render.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        // Set 'secure' => true once the site is served over HTTPS.
        'secure' => !empty($_SERVER['HTTPS']),
    ]);
    session_start();
}

function is_logged_in(): bool
{
    return !empty($_SESSION['admin']);
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ./index.php');
        exit;
    }
}

/** Attempt a login. Returns true when the credentials are correct. */
function attempt_login(string $user, string $password): bool
{
    // hash_equals keeps the username check constant-time; password_verify
    // already is. Both run regardless of which one fails, so a wrong username
    // and a wrong password take the same time.
    $userOk = hash_equals(ADMIN_USER, $user);
    $passOk = password_verify($password, ADMIN_PASSWORD_HASH);

    if ($userOk && $passOk) {
        session_regenerate_id(true);
        $_SESSION['admin'] = $user;
        return true;
    }
    return false;
}

function logout(): void
{
    $_SESSION = [];
    session_destroy();
}

/** CSRF token for this session, created on first use. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/** Reject any POST that does not carry the session's CSRF token. */
function check_csrf(): void
{
    $sent = (string) ($_POST['csrf'] ?? '');
    if (!hash_equals(csrf_token(), $sent)) {
        http_response_code(400);
        exit('Invalid or expired form token. Go back, reload the page and try again.');
    }
}

/** True when the admin password is still the shipped placeholder. */
function using_default_password(): bool
{
    return password_verify('change-me-now', ADMIN_PASSWORD_HASH);
}
