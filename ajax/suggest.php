<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

$field = str_clean($_GET['field'] ?? '', 20);
$q = str_clean($_GET['q'] ?? '', 100);
$mine = (string)($_GET['mine'] ?? '');

if ($q === '' || !in_array($field, ['title', 'author', 'genre'], true)) {
    echo json_encode(['items' => []], JSON_UNESCAPED_UNICODE);
    exit;
}

$sqlField = match ($field) {
    'title' => 'title',
    'author' => 'author',
    'genre' => 'genre',
};

$stmt = $pdo->prepare("SELECT DISTINCT {$sqlField} AS v FROM books WHERE {$sqlField} LIKE :q ORDER BY v ASC LIMIT 8");
$params = [':q' => $q . '%'];
if ($mine === '1' && books_has_user_id($pdo) && auth_user()) {
    $stmt = $pdo->prepare("SELECT DISTINCT {$sqlField} AS v FROM books WHERE user_id = :uid AND {$sqlField} LIKE :q ORDER BY v ASC LIMIT 8");
    $params[':uid'] = (int)auth_user()['id'];
}
$stmt->execute($params);
$items = array_values(array_filter(array_map(fn($r) => (string)$r['v'], $stmt->fetchAll())));

echo json_encode(['items' => $items], JSON_UNESCAPED_UNICODE);
