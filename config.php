<?php
declare(strict_types=1);

/**
 * App configuration.
 * - For production (mi-linux), set env vars instead of editing this file.
 */
return [
    'app' => [
        'name' => getenv('APP_NAME') ?: 'Library Management System',
        // If your app is hosted in a sub-folder, set APP_BASE_URL (e.g. /~abc123/library-management-system)
        'base_url' => rtrim(getenv('APP_BASE_URL') ?: '', '/'),
        'session_name' => getenv('APP_SESSION_NAME') ?: 'library_system',
        // Set APP_DEBUG=1 in development to show detailed errors on the 500 page.
        'debug' => (getenv('APP_DEBUG') ?: '') === '1',
    ],
    'db' => [
        'host' => getenv('DB_HOST') ?: '127.0.0.1',
        'port' => (int)(getenv('DB_PORT') ?: 3306),
        'name' => getenv('DB_NAME') ?: 'library_db',
        'user' => getenv('DB_USER') ?: 'root',
        'pass' => getenv('DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
];
