<?php
declare(strict_types=1);

function auth_show_login(Twig\Environment $twig): void
{
    captcha_new();
    echo $twig->render('auth/login.twig');
}

function auth_do_login(PDO $pdo): void
{
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);

    $username = str_clean($_POST['username'] ?? '', 100);
    $password = (string)($_POST['password'] ?? '');
    $captcha = (string)($_POST['captcha'] ?? '');

    if ($username === '' || $password === '') {
        flash_set('error', 'Username and password are required.');
        redirect(url('/public/login'));
    }
    if (!captcha_validate($captcha)) {
        flash_set('error', 'Captcha is incorrect.');
        redirect(url('/public/login'));
    }

    $passCol = users_password_col($pdo);
    $roleSelect = users_has_role($pdo) ? 'role' : "'user' AS role";
    $stmt = $pdo->prepare("SELECT id, username, {$passCol} AS password_hash, {$roleSelect} FROM users WHERE username = :u LIMIT 1");
    $stmt->execute([':u' => $username]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string)$user['password_hash'])) {
        flash_set('error', 'Invalid login.');
        redirect(url('/public/login'));
    }

    auth_login($user);
    flash_set('success', 'Welcome back, ' . $user['username'] . '!');
    redirect(url('/public/books'));
}

function auth_show_register(Twig\Environment $twig): void
{
    captcha_new();
    echo $twig->render('auth/register.twig');
}

function auth_do_register(PDO $pdo): void
{
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);

    $username = str_clean($_POST['username'] ?? '', 100);
    $password = (string)($_POST['password'] ?? '');
    $password2 = (string)($_POST['password_confirm'] ?? '');
    $captcha = (string)($_POST['captcha'] ?? '');

    if ($username === '' || $password === '') {
        flash_set('error', 'Username and password are required.');
        redirect(url('/public/register'));
    }
    if (!preg_match('/^[a-zA-Z0-9_.-]{3,100}$/', $username)) {
        flash_set('error', 'Username must be 3-100 chars (letters, numbers, _ . -).');
        redirect(url('/public/register'));
    }
    if ($password !== $password2) {
        flash_set('error', 'Passwords do not match.');
        redirect(url('/public/register'));
    }
    if (strlen($password) < 8) {
        flash_set('error', 'Password must be at least 8 characters.');
        redirect(url('/public/register'));
    }
    if (!captcha_validate($captcha)) {
        flash_set('error', 'Captcha is incorrect.');
        redirect(url('/public/register'));
    }

    $stmt = $pdo->prepare('SELECT id FROM users WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    if ($stmt->fetch()) {
        flash_set('error', 'Username already exists.');
        redirect(url('/public/register'));
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $passCol = users_password_col($pdo);

    if (users_has_role($pdo)) {
        $insert = $pdo->prepare("INSERT INTO users (username, {$passCol}, role) VALUES (:u, :p, :r)");
        $insert->execute([':u' => $username, ':p' => $hash, ':r' => 'user']);
    } else {
        $insert = $pdo->prepare("INSERT INTO users (username, {$passCol}) VALUES (:u, :p)");
        $insert->execute([':u' => $username, ':p' => $hash]);
    }

    flash_set('success', 'Account created. Please login.');
    redirect(url('/public/login'));
}

function auth_do_logout(): void
{
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);
    auth_logout();
    flash_set('success', 'Logged out.');
    redirect(url('/public/books'));
}
