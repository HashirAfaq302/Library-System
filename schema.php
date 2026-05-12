<?php
declare(strict_types=1);

function db_table_columns(PDO $pdo, string $table): array
{
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) {
        return [];
    }

    static $cache = [];
    if (isset($cache[$table])) {
        return $cache[$table];
    }

    $cols = [];
    $stmt = $pdo->query('SHOW COLUMNS FROM `' . $table . '`');
    foreach ($stmt->fetchAll() as $row) {
        $name = (string)($row['Field'] ?? '');
        if ($name !== '') {
            $cols[$name] = true;
        }
    }

    $cache[$table] = $cols;
    return $cols;
}

function books_year_col(PDO $pdo): string
{
    $cols = db_table_columns($pdo, 'books');
    return isset($cols['published_year']) ? 'published_year' : 'year';
}

function books_has_isbn(PDO $pdo): bool
{
    $cols = db_table_columns($pdo, 'books');
    return isset($cols['isbn']);
}

function books_has_user_id(PDO $pdo): bool
{
    $cols = db_table_columns($pdo, 'books');
    return isset($cols['user_id']);
}

function books_has_created_at(PDO $pdo): bool
{
    $cols = db_table_columns($pdo, 'books');
    return isset($cols['created_at']);
}

function users_password_col(PDO $pdo): string
{
    $cols = db_table_columns($pdo, 'users');
    return isset($cols['password_hash']) ? 'password_hash' : 'password';
}

function users_has_role(PDO $pdo): bool
{
    $cols = db_table_columns($pdo, 'users');
    return isset($cols['role']);
}
