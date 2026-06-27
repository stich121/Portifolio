<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query(
        'SELECT id, name, phone, class_day AS classDay,
                TIME_FORMAT(class_time, "%H:%i") AS classTime,
                duration_minutes AS durationMinutes, notes, is_active AS isActive,
                created_at AS createdAt
         FROM alunos
         ORDER BY class_day ASC, class_time ASC, name ASC'
    );

    json_response(['students' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = request_data();
    $name = trim((string)($data['name'] ?? ''));
    $phone = trim((string)($data['phone'] ?? ''));
    $classDay = (int)($data['classDay'] ?? -1);
    $classTime = trim((string)($data['classTime'] ?? ''));
    $notes = trim((string)($data['notes'] ?? ''));

    if ($name === '') {
        json_response(['error' => 'Informe o nome do aluno.'], 422);
    }

    if ($classDay < 0 || $classDay > 6 || !preg_match('/^\d{2}:\d{2}$/', $classTime)) {
        json_response(['error' => 'Informe dia e horario validos.'], 422);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO alunos (name, phone, class_day, class_time, duration_minutes, notes)
         VALUES (:name, :phone, :class_day, :class_time, 60, :notes)'
    );
    $stmt->execute([
        ':name' => $name,
        ':phone' => $phone !== '' ? $phone : null,
        ':class_day' => $classDay,
        ':class_time' => $classTime . ':00',
        ':notes' => $notes !== '' ? $notes : null,
    ]);

    json_response(['ok' => true, 'id' => (int)$pdo->lastInsertId()], 201);
}

if ($method === 'PATCH') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);
    $isActive = (int)($data['isActive'] ?? 1);

    if ($id < 1) {
        json_response(['error' => 'Aluno invalido.'], 422);
    }

    $stmt = $pdo->prepare('UPDATE alunos SET is_active = :is_active WHERE id = :id');
    $stmt->execute([':is_active' => $isActive ? 1 : 0, ':id' => $id]);

    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);

    if ($id < 1) {
        json_response(['error' => 'Aluno invalido.'], 422);
    }

    $stmt = $pdo->prepare('DELETE FROM alunos WHERE id = :id');
    $stmt->execute([':id' => $id]);

    json_response(['ok' => true]);
}

json_response(['error' => 'Metodo nao permitido.'], 405);
