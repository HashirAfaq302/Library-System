<?php
declare(strict_types=1);

function books_list(PDO $pdo, Twig\Environment $twig, ?int $ownerUserId, string $mode, string $actionPath): void
{
    $qTitle = str_clean($_GET['title'] ?? '', 255);
    $qAuthor = str_clean($_GET['author'] ?? '', 255);
    $qGenre = str_clean($_GET['genre'] ?? '', 100);
    $year = int_or_null($_GET['year'] ?? null);

    $yearCol = books_year_col($pdo);
    $hasIsbn = books_has_isbn($pdo);
    $hasUserId = books_has_user_id($pdo);
    $hasCreatedAt = books_has_created_at($pdo);

    $isbnSelect = $hasIsbn ? 'b.isbn' : "'' AS isbn";
    $userIdSelect = $hasUserId ? 'b.user_id' : 'NULL AS user_id';
    $createdAtSelect = $hasCreatedAt ? 'b.created_at' : 'NULL AS created_at';

    $sql = "SELECT b.id, b.title, b.author, b.genre, b.{$yearCol} AS published_year, {$isbnSelect}, {$userIdSelect}, {$createdAtSelect}";
    if ($hasUserId) {
        $sql .= ", u.username AS owner_username";
    } else {
        $sql .= ", NULL AS owner_username";
    }
    $sql .= ' FROM books b';
    if ($hasUserId) {
        $sql .= ' LEFT JOIN users u ON u.id = b.user_id';
    }
    $sql .= ' WHERE 1=1';

    $params = [];

    if ($ownerUserId !== null) {
        if (!$hasUserId) {
            echo $twig->render('books/index.twig', [
                'books' => [],
                'filters' => ['title' => $qTitle, 'author' => $qAuthor, 'genre' => $qGenre, 'year' => $year],
                'mode' => $mode,
                'action' => url($actionPath),
            ]);
            return;
        }
        $sql .= ' AND b.user_id = :uid';
        $params[':uid'] = $ownerUserId;
    }

    if ($qTitle !== '') {
        $sql .= ' AND b.title LIKE :title';
        $params[':title'] = '%' . $qTitle . '%';
    }
    if ($qAuthor !== '') {
        $sql .= ' AND b.author LIKE :author';
        $params[':author'] = '%' . $qAuthor . '%';
    }
    if ($qGenre !== '') {
        $sql .= ' AND b.genre = :genre';
        $params[':genre'] = $qGenre;
    }
    if ($year !== null) {
        $sql .= " AND b.{$yearCol} = :year";
        $params[':year'] = $year;
    }

    if ($hasCreatedAt) {
        $sql .= ' ORDER BY b.created_at DESC, b.id DESC LIMIT 200';
    } else {
        $sql .= ' ORDER BY b.id DESC LIMIT 200';
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $books = $stmt->fetchAll();

    $auth = auth_user();
    $authId = $auth ? (int)$auth['id'] : null;
    foreach ($books as &$b) {
        $bookOwner = isset($b['user_id']) ? int_or_null($b['user_id']) : null;
        $b['can_edit'] = ($authId !== null && $bookOwner !== null && $bookOwner === $authId);
    }
    unset($b);

    echo $twig->render('books/index.twig', [
        'books' => $books,
        'filters' => [
            'title' => $qTitle,
            'author' => $qAuthor,
            'genre' => $qGenre,
            'year' => $year,
        ],
        'mode' => $mode,
        'action' => url($actionPath),
    ]);
}

function books_index(PDO $pdo, Twig\Environment $twig): void
{
    books_list($pdo, $twig, null, 'browse', '/public/books');
}

function books_my_index(PDO $pdo, Twig\Environment $twig): void
{
    require_login();
    $auth = auth_user();
    $uid = $auth ? (int)$auth['id'] : 0;
    books_list($pdo, $twig, $uid, 'mine', '/public/my-books');
}

function books_view(PDO $pdo, Twig\Environment $twig, int $id): void
{
    $yearCol = books_year_col($pdo);
    $isbnSelect = books_has_isbn($pdo) ? 'isbn' : "'' AS isbn";
    $userIdSelect = books_has_user_id($pdo) ? 'user_id' : 'NULL AS user_id';
    $createdAtSelect = books_has_created_at($pdo) ? 'created_at' : 'NULL AS created_at';
    $stmt = $pdo->prepare("SELECT id, title, author, genre, {$yearCol} AS published_year, {$isbnSelect}, {$userIdSelect}, description, {$createdAtSelect} FROM books WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch();
    if (!$book) {
        http_response_code(404);
        echo $twig->render('errors/404.twig');
        return;
    }

    $auth = auth_user();
    $authId = $auth ? (int)$auth['id'] : null;
    $ownerId = int_or_null($book['user_id'] ?? null);
    $canEdit = ($authId !== null && $ownerId !== null && $ownerId === $authId);

    echo $twig->render('books/view.twig', ['book' => $book, 'can_edit' => $canEdit]);
}

function books_new(Twig\Environment $twig): void
{
    require_login();
    echo $twig->render('books/form.twig', [
        'mode' => 'create',
        'book' => [
            'title' => '',
            'author' => '',
            'genre' => '',
            'published_year' => '',
            'isbn' => '',
            'description' => '',
        ],
    ]);
}

function books_create(PDO $pdo): void
{
    require_login();
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);

    $title = str_clean($_POST['title'] ?? '', 255);
    $author = str_clean($_POST['author'] ?? '', 255);
    $genre = str_clean($_POST['genre'] ?? '', 100);
    $year = int_or_null($_POST['published_year'] ?? null);
    $isbn = str_clean($_POST['isbn'] ?? '', 20);
    $description = str_clean($_POST['description'] ?? '', 2000);

    if ($title === '' || $author === '' || $genre === '' || $year === null) {
        flash_set('error', 'Title, author, genre and year are required.');
        redirect(url('/public/books/new'));
    }
    if ($year < 1000 || $year > ((int)date('Y') + 1)) {
        flash_set('error', 'Published year looks invalid.');
        redirect(url('/public/books/new'));
    }

    $yearCol = books_year_col($pdo);
    $hasIsbn = books_has_isbn($pdo);
    $hasUserId = books_has_user_id($pdo);
    $auth = auth_user();
    $uid = $auth ? (int)$auth['id'] : null;

    if ($hasIsbn) {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, genre, {$yearCol}, isbn, description" . ($hasUserId ? ", user_id" : "") . ")
            VALUES (:t, :a, :g, :y, :i, :d" . ($hasUserId ? ", :uid" : "") . ")
        ");
        $params = [
            ':t' => $title,
            ':a' => $author,
            ':g' => $genre,
            ':y' => $year,
            ':i' => $isbn,
            ':d' => $description,
        ];
        if ($hasUserId) {
            $params[':uid'] = $uid;
        }
        $stmt->execute($params);
    } else {
        $stmt = $pdo->prepare("
            INSERT INTO books (title, author, genre, {$yearCol}, description" . ($hasUserId ? ", user_id" : "") . ")
            VALUES (:t, :a, :g, :y, :d" . ($hasUserId ? ", :uid" : "") . ")
        ");
        $params = [
            ':t' => $title,
            ':a' => $author,
            ':g' => $genre,
            ':y' => $year,
            ':d' => $description,
        ];
        if ($hasUserId) {
            $params[':uid'] = $uid;
        }
        $stmt->execute($params);
    }

    flash_set('success', 'Book added.');
    redirect(url('/public/books'));
}

function books_edit(PDO $pdo, Twig\Environment $twig, int $id): void
{
    require_login();

    $yearCol = books_year_col($pdo);
    $isbnSelect = books_has_isbn($pdo) ? 'isbn' : "'' AS isbn";
    $userIdSelect = books_has_user_id($pdo) ? 'user_id' : 'NULL AS user_id';
    $stmt = $pdo->prepare("SELECT id, title, author, genre, {$yearCol} AS published_year, {$isbnSelect}, {$userIdSelect}, description FROM books WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $book = $stmt->fetch();
    if (!$book) {
        http_response_code(404);
        echo $twig->render('errors/404.twig');
        return;
    }

    if (books_has_user_id($pdo)) {
        $auth = auth_user();
        $authId = $auth ? (int)$auth['id'] : null;
        $ownerId = int_or_null($book['user_id'] ?? null);
        if ($authId === null || $ownerId === null || $ownerId !== $authId) {
            http_response_code(403);
            flash_set('error', 'You can only edit your own books.');
            redirect(url('/public/books'));
        }
    }

    echo $twig->render('books/form.twig', [
        'mode' => 'edit',
        'book' => $book,
    ]);
}

function books_update(PDO $pdo, int $id): void
{
    require_login();
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);

    $title = str_clean($_POST['title'] ?? '', 255);
    $author = str_clean($_POST['author'] ?? '', 255);
    $genre = str_clean($_POST['genre'] ?? '', 100);
    $year = int_or_null($_POST['published_year'] ?? null);
    $isbn = str_clean($_POST['isbn'] ?? '', 20);
    $description = str_clean($_POST['description'] ?? '', 2000);

    if ($title === '' || $author === '' || $genre === '' || $year === null) {
        flash_set('error', 'Title, author, genre and year are required.');
        redirect(url("/public/books/{$id}/edit"));
    }

    if (books_has_user_id($pdo)) {
        $stmt = $pdo->prepare('SELECT user_id FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $auth = auth_user();
        $authId = $auth ? (int)$auth['id'] : null;
        $ownerId = int_or_null($row['user_id'] ?? null);
        if ($authId === null || $ownerId === null || $ownerId !== $authId) {
            http_response_code(403);
            flash_set('error', 'You can only update your own books.');
            redirect(url('/public/books'));
        }
    }

    $yearCol = books_year_col($pdo);
    $hasIsbn = books_has_isbn($pdo);

    if ($hasIsbn) {
        $stmt = $pdo->prepare("
            UPDATE books
            SET title = :t, author = :a, genre = :g, {$yearCol} = :y, isbn = :i, description = :d
            WHERE id = :id
        ");
        $stmt->execute([
            ':t' => $title,
            ':a' => $author,
            ':g' => $genre,
            ':y' => $year,
            ':i' => $isbn,
            ':d' => $description,
            ':id' => $id,
        ]);
    } else {
        $stmt = $pdo->prepare("
            UPDATE books
            SET title = :t, author = :a, genre = :g, {$yearCol} = :y, description = :d
            WHERE id = :id
        ");
        $stmt->execute([
            ':t' => $title,
            ':a' => $author,
            ':g' => $genre,
            ':y' => $year,
            ':d' => $description,
            ':id' => $id,
        ]);
    }

    flash_set('success', 'Book updated.');
    redirect(url("/public/books/{$id}"));
}

function books_delete(PDO $pdo, int $id): void
{
    require_login();
    require_post();
    csrf_validate($_POST['_csrf'] ?? null);

    if (books_has_user_id($pdo)) {
        $stmt = $pdo->prepare('SELECT user_id FROM books WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        $auth = auth_user();
        $authId = $auth ? (int)$auth['id'] : null;
        $ownerId = int_or_null($row['user_id'] ?? null);
        if ($authId === null || $ownerId === null || $ownerId !== $authId) {
            http_response_code(403);
            flash_set('error', 'You can only delete your own books.');
            redirect(url('/public/books'));
        }
    }

    $stmt = $pdo->prepare('DELETE FROM books WHERE id = :id');
    $stmt->execute([':id' => $id]);

    flash_set('success', 'Book deleted.');
    redirect(url('/public/books'));
}
