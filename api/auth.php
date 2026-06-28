<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    json_response([
        'authenticated' => !empty($_SESSION['presence_authenticated']),
        'teacher' => $_SESSION['teacher'] ?? null,
    ]);
}

if ($method === 'DELETE') {
    $_SESSION = [];
    session_destroy();
    json_response(['ok' => true]);
}

if ($method !== 'POST') {
    json_response(['error' => 'Método não permitido.'], 405);
}

$data = request_data();
$username = trim((string)($data['username'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['error' => 'Informe login e senha.'], 422);
}

$stmt = db()->prepare(
    'SELECT id, username, password_hash, access_level
     FROM access_users
     WHERE username = :username AND is_active = 1
     LIMIT 1'
);
$stmt->execute([':username' => $username]);
$row = $stmt->fetch();

if (!$row || !hash_equals((string)$row['password_hash'], hash('sha256', $password))) {
    json_response(['error' => 'Login ou senha incorretos.'], 401);
}

$level = (int)$row['access_level'];
if ($level < 1 || $level > 3) {
    $level = 1;
}

$_SESSION['presence_authenticated'] = true;
$_SESSION['teacher'] = [
    'id' => (int)$row['id'],
    'username' => (string)$row['username'],
    'accessLevel' => $level,
];

json_response(['ok' => true, 'teacher' => $_SESSION['teacher']]);
