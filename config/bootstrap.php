<?php
declare(strict_types=1);

use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFunction;

require_once __DIR__ . '/../vendor/autoload.php';

$config = require __DIR__ . '/config.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/schema.php';

app_start_session($config['app']['session_name']);

$pdo = db_connect($config['db']);

$loader = new FilesystemLoader(__DIR__ . '/../templates');
$twig = new Environment($loader, [
    'cache' => false,
    'autoescape' => 'html',
]);

$baseUrl = app_base_url($config['app']['base_url']);
if (!defined('APP_BASE_URL')) {
    define('APP_BASE_URL', $baseUrl);
}

$twig->addGlobal('app', [
    'name' => $config['app']['name'],
    'base_url' => $baseUrl,
    'debug' => (bool)($config['app']['debug'] ?? false),
]);

$twig->addGlobal('auth', [
    'user' => auth_user(),
]);

$twig->addFunction(new TwigFunction('csrf_field', function (): string {
    $token = csrf_token();
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}, ['is_safe' => ['html']]));

$twig->addFunction(new TwigFunction('flash_get', function (string $key): ?string {
    return flash_get($key);
}));

$twig->addFunction(new TwigFunction('captcha_question', function (): string {
    return captcha_question();
}));
