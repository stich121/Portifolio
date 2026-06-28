<?php
declare(strict_types=1);

$configPaths = [
    dirname(__DIR__, 2) . '/config.php',
    __DIR__ . '/config.php',
];

$configPath = null;

foreach ($configPaths as $path) {
    if (file_exists($path)) {
        $configPath = $path;
        break;
    }
}

if ($configPath === null) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Arquivo config.php não configurado fora do public_html.']);
    exit;
}

require_once $configPath;

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_NAME,
        DB_CHARSET
    );

    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}
