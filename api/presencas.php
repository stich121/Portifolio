<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT id, name, company, status, created_at AS createdAt
         FROM presencas
         ORDER BY created_at DESC, id DESC'
    );

    json_response(['records' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = request_data();
    $name = trim((string)($data['name'] ?? ''));
    $company = trim((string)($data['company'] ?? ''));
    $status = trim((string)($data['status'] ?? 'Presente'));

    if ($name === '') {
        json_response(['error' => 'Informe o nome.'], 422);
    }

    if (!valid_status($status)) {
        json_response(['error' => 'Status invalido.'], 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO presencas (name, company, status)
         VALUES (:name, :company, :status)'
    );
    $stmt->execute([
        ':name' => $name,
        ':company' => $company,
        ':status' => $status,
    ]);

    json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PATCH') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);
    $status = trim((string)($data['status'] ?? ''));

    if ($id < 1 || !valid_status($status)) {
        json_response(['error' => 'Dados invalidos.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE presencas SET status = :status WHERE id = :id');
    $stmt->execute([':status' => $status, ':id' => $id]);

    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);

    if ($id > 0) {
        $stmt = $pdo->prepare('DELETE FROM presencas WHERE id = :id');
        $stmt->execute([':id' => $id]);
        json_response(['ok' => true]);
    }

    $pdo->exec('DELETE FROM presencas');
    json_response(['ok' => true]);
}

json_response(['error' => 'Metodo nao permitido.'], 405);
