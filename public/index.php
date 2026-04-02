<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';
require_once __DIR__ . '/../controllers/auth.php';
require_once __DIR__ . '/../controllers/books.php';

header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');

// Basic router (supports pretty URLs via public/.htaccess)
$path = $_GET['path'] ?? null;
if (!is_string($path) || $path === '') {
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
}

// Strip base + /public
$base = $config['app']['base_url'] !== '' ? $config['app']['base_url'] : app_base_url('');
if ($base !== '' && str_starts_with($path, $base)) {
    $path = substr($path, strlen($base)) ?: '/';
}
if (str_starts_with($path, '/public')) {
    $path = substr($path, strlen('/public')) ?: '/';
}

$path = '/' . ltrim($path, '/');

try {
    // Make current route available to Twig (navbar active state, etc.)
    $twig->addGlobal('route', ['path' => $path]);

    // Home
    if ($path === '/' || $path === '/index.php') {
        redirect(url('/public/books'));
    }

    // Auth
    if ($path === '/login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        auth_show_login($twig);
        exit;
    }
    if ($path === '/login' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        auth_do_login($pdo);
        exit;
    }
    if ($path === '/register' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        auth_show_register($twig);
        exit;
    }
    if ($path === '/register' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        auth_do_register($pdo);
        exit;
    }
    if ($path === '/logout' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        auth_do_logout();
        exit;
    }

    // Books
    if ($path === '/books' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        books_index($pdo, $twig);
        exit;
    }
    if ($path === '/my-books' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        books_my_index($pdo, $twig);
        exit;
    }
    if ($path === '/books/new' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        books_new($twig);
        exit;
    }
    if ($path === '/books/new' && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        books_create($pdo);
        exit;
    }

    if (preg_match('~^/books/(\d+)$~', $path, $m) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        books_view($pdo, $twig, (int)$m[1]);
        exit;
    }
    if (preg_match('~^/books/(\d+)/edit$~', $path, $m) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
        books_edit($pdo, $twig, (int)$m[1]);
        exit;
    }
    if (preg_match('~^/books/(\d+)/edit$~', $path, $m) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        books_update($pdo, (int)$m[1]);
        exit;
    }
    if (preg_match('~^/books/(\d+)/delete$~', $path, $m) && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
        books_delete($pdo, (int)$m[1]);
        exit;
    }

    http_response_code(404);
    echo $twig->render('errors/404.twig');
} catch (Throwable $e) {
    http_response_code(500);
    error_log('[LibrarySystem] ' . $e::class . ': ' . $e->getMessage());
    echo $twig->render('errors/500.twig', [
        'message' => 'Unexpected error. Check your configuration and database.',
        'debug' => (bool)($config['app']['debug'] ?? false),
        'exception' => $e,
    ]);
}
