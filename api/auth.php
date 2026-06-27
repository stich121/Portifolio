<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    json_response(['authenticated' => !empty($_SESSION['presence_authenticated'])]);
}

if ($method === 'DELETE') {
    $_SESSION = [];
    session_destroy();
    json_response(['ok' => true]);
}

if ($method !== 'POST') {
    json_response(['error' => 'Metodo nao permitido.'], 405);
}

$data = request_data();
$code = trim((string)($data['code'] ?? ''));

if ($code === '') {
    json_response(['error' => 'Informe o codigo de acesso.'], 422);
}

$stmt = db()->query('SELECT code_hash FROM access_codes WHERE is_active = 1 ORDER BY id DESC LIMIT 1');
$row = $stmt->fetch();

if (!$row || !hash_equals((string)$row['code_hash'], hash('sha256', $code))) {
    json_response(['error' => 'Codigo incorreto.'], 401);
}

$_SESSION['presence_authenticated'] = true;
json_response(['ok' => true]);
