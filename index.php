<?php
declare(strict_types=1);

// Shared-hosting friendly entrypoint:
// - If your host's document root is the project directory (public_html),
//   this file prevents a 403 (no index) and forwards the request to the app.
require __DIR__ . '/public/index.php';

