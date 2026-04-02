<?php
declare(strict_types=1);

function app_base_url(string $configuredBaseUrl): string
{
    if ($configuredBaseUrl !== '') {
        return $configuredBaseUrl;
    }

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Common case: /library-system/public/index.php => base is /library-system
    $publicDir = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
    $base = preg_replace('~/public$~', '', $publicDir) ?? $publicDir;
    return $base === '' ? '' : $base;
}

function app_start_session(string $sessionName): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    session_name($sessionName);

    $cookiePath = defined('APP_BASE_URL') ? ((string)APP_BASE_URL ?: '/') : '/';
    if ($cookiePath === '') {
        $cookiePath = '/';
    }
    // Session cookies should remain valid for the whole app folder (important on mi-linux subfolders).
    session_set_cookie_params([
        'path' => $cookiePath,
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    ]);
    session_start();
}

function e(?string $value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function url(string $path): string
{
    $base = defined('APP_BASE_URL') ? (string)APP_BASE_URL : app_base_url('');
    $path = '/' . ltrim($path, '/');
    return $base . $path;
}

function flash_set(string $key, string $value): void
{
    $_SESSION['_flash'][$key] = $value;
}

function flash_get(string $key): ?string
{
    if (!isset($_SESSION['_flash'][$key])) {
        return null;
    }
    $value = (string)$_SESSION['_flash'][$key];
    unset($_SESSION['_flash'][$key]);
    return $value;
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return (string)$_SESSION['_csrf'];
}

function csrf_validate(?string $token): void
{
    $expected = $_SESSION['_csrf'] ?? '';
    if (!is_string($expected) || $expected === '' || !is_string($token) || !hash_equals($expected, $token)) {
        http_response_code(400);
        exit('Invalid CSRF token.');
    }
}

function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }
}

function auth_user(): ?array
{
    return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
}

function require_login(): void
{
    if (!auth_user()) {
        flash_set('error', 'Please login to continue.');
        redirect(url('/public/login'));
    }
}

function auth_login(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => (int)$user['id'],
        'username' => (string)$user['username'],
        'role' => (string)($user['role'] ?? 'user'),
    ];
}

function auth_logout(): void
{
    unset($_SESSION['user']);
    session_regenerate_id(true);
}

function captcha_new(): void
{
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['_captcha'] = [
        'q' => "What is {$a} + {$b}?",
        'a' => (string)($a + $b),
        't' => time(),
    ];
}

function captcha_question(): string
{
    if (empty($_SESSION['_captcha']) || !is_array($_SESSION['_captcha'])) {
        captcha_new();
    }
    return (string)($_SESSION['_captcha']['q'] ?? 'Captcha');
}

function captcha_validate(?string $answer): bool
{
    if (empty($_SESSION['_captcha']) || !is_array($_SESSION['_captcha'])) {
        captcha_new();
        return false;
    }
    $expected = (string)($_SESSION['_captcha']['a'] ?? '');
    $given = trim((string)$answer);
    $ok = ($expected !== '' && hash_equals($expected, $given));
    captcha_new(); // one-time
    return $ok;
}

function str_clean(?string $value, int $maxLen = 255): string
{
    $value = trim((string)$value);
    $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
    $value = strip_tags($value);
    if (mb_strlen($value) > $maxLen) {
        $value = mb_substr($value, 0, $maxLen);
    }
    return $value;
}

function int_or_null(mixed $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $filtered = filter_var($value, FILTER_VALIDATE_INT);
    return ($filtered === false) ? null : (int)$filtered;
}
