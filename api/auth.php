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
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['error' => 'Informe login e senha.'], 422);
}

$stmt = db()->prepare(
    'SELECT password_hash
     FROM access_users
     WHERE username = :username AND is_active = 1
     LIMIT 1'
);
$stmt->execute([':username' => $username]);
$row = $stmt->fetch();

if (!$row || !hash_equals((string)$row['password_hash'], hash('sha256', $password))) {
    json_response(['error' => 'Login ou senha incorretos.'], 401);
}

$_SESSION['presence_authenticated'] = true;
json_response(['ok' => true]);
