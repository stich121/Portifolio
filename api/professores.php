<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

require_access_level(3);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT id, username, access_level AS accessLevel, is_active AS isActive, created_at AS createdAt
         FROM access_users
         ORDER BY access_level DESC, username ASC'
    );

    json_response(['teachers' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = request_data();
    $username = trim((string)($data['username'] ?? ''));
    $password = (string)($data['password'] ?? '');
    $level = (int)($data['accessLevel'] ?? 1);

    if ($username === '' || $password === '') {
        json_response(['error' => 'Informe login e senha do professor.'], 422);
    }

    if ($level < 1 || $level > 3) {
        json_response(['error' => 'Nível inválido. Use 1, 2 ou 3.'], 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO access_users (username, password_hash, access_level, is_active)
         VALUES (:username, :password_hash, :access_level, 1)'
    );

    try {
        $stmt->execute([
            ':username' => $username,
            ':password_hash' => hash('sha256', $password),
            ':access_level' => $level,
        ]);
    } catch (PDOException $exception) {
        if ($exception->getCode() === '23000') {
            json_response(['error' => 'Já existe um professor com esse login.'], 409);
        }
        throw $exception;
    }

    json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PATCH') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);
    $level = isset($data['accessLevel']) ? (int)$data['accessLevel'] : null;
    $isActive = isset($data['isActive']) ? (int)$data['isActive'] : null;

    if ($id < 1) {
        json_response(['error' => 'Professor inválido.'], 422);
    }

    if ($level !== null) {
        if ($level < 1 || $level > 3) {
            json_response(['error' => 'Nível inválido.'], 422);
        }
        $stmt = $pdo->prepare('UPDATE access_users SET access_level = :access_level WHERE id = :id');
        $stmt->execute([':access_level' => $level, ':id' => $id]);
    }

    if ($isActive !== null) {
        $stmt = $pdo->prepare('UPDATE access_users SET is_active = :is_active WHERE id = :id');
        $stmt->execute([':is_active' => $isActive ? 1 : 0, ':id' => $id]);
    }

    json_response(['ok' => true]);
}

json_response(['error' => 'Método não permitido.'], 405);
