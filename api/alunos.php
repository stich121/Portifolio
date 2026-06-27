<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

if ($method === 'GET') {
    $teacherId = resolve_teacher_scope((int)($_GET['teacherId'] ?? 0));
    $stmt = $pdo->prepare(
        'SELECT alunos.id, alunos.teacher_id AS teacherId, access_users.username AS teacherName,
                alunos.name, alunos.phone, alunos.class_day AS classDay,
                TIME_FORMAT(alunos.class_time, "%H:%i") AS classTime,
                alunos.duration_minutes AS durationMinutes, alunos.notes,
                alunos.is_active AS isActive, alunos.created_at AS createdAt
         FROM alunos
         INNER JOIN access_users ON access_users.id = alunos.teacher_id
         WHERE alunos.teacher_id = :teacher_id
         ORDER BY alunos.class_day ASC, alunos.class_time ASC, alunos.name ASC'
    );
    $stmt->execute([':teacher_id' => $teacherId]);

    json_response(['teacherId' => $teacherId, 'students' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = request_data();
    $teacherId = resolve_teacher_scope((int)($data['teacherId'] ?? 0));
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
        'INSERT INTO alunos (teacher_id, name, phone, class_day, class_time, duration_minutes, notes)
         VALUES (:teacher_id, :name, :phone, :class_day, :class_time, 60, :notes)'
    );
    $stmt->execute([
        ':teacher_id' => $teacherId,
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

    $allowedTeacherId = resolve_teacher_scope((int)($data['teacherId'] ?? 0));
    $stmt = $pdo->prepare('UPDATE alunos SET is_active = :is_active WHERE id = :id AND teacher_id = :teacher_id');
    $stmt->execute([':is_active' => $isActive ? 1 : 0, ':id' => $id, ':teacher_id' => $allowedTeacherId]);

    json_response(['ok' => true]);
}

if ($method === 'DELETE') {
    $data = request_data();
    $id = (int)($data['id'] ?? 0);

    if ($id < 1) {
        json_response(['error' => 'Aluno invalido.'], 422);
    }

    $allowedTeacherId = resolve_teacher_scope((int)($data['teacherId'] ?? 0));
    $stmt = $pdo->prepare('DELETE FROM alunos WHERE id = :id AND teacher_id = :teacher_id');
    $stmt->execute([':id' => $id, ':teacher_id' => $allowedTeacherId]);

    json_response(['ok' => true]);
}

json_response(['error' => 'Metodo nao permitido.'], 405);
