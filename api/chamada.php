<?php
declare(strict_types=1);

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';

require_auth();

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$pdo = db();

function valid_date_value(string $date): bool
{
    $parsed = DateTime::createFromFormat('Y-m-d', $date);
    return $parsed instanceof DateTime && $parsed->format('Y-m-d') === $date;
}

if ($method === 'GET') {
    $date = trim((string)($_GET['date'] ?? date('Y-m-d')));
    $teacherId = resolve_teacher_scope((int)($_GET['teacherId'] ?? 0));

    if (!valid_date_value($date)) {
        json_response(['error' => 'Data invalida.'], 422);
    }

    $stmt = $pdo->prepare(
        'SELECT alunos.id AS studentId, alunos.teacher_id AS teacherId,
                access_users.username AS teacherName, alunos.name, alunos.phone,
                alunos.class_day AS classDay,
                TIME_FORMAT(alunos.class_time, "%H:%i") AS classTime,
                COALESCE(chamadas.status, \'Pendente\') AS status,
                chamadas.id AS attendanceId
         FROM alunos
         INNER JOIN access_users ON access_users.id = alunos.teacher_id
         LEFT JOIN chamadas
           ON chamadas.student_id = alunos.id
          AND chamadas.attendance_date = :attendance_date
         WHERE alunos.is_active = 1 AND alunos.teacher_id = :teacher_id
         ORDER BY alunos.class_day ASC, alunos.class_time ASC, alunos.name ASC'
    );
    $stmt->execute([':attendance_date' => $date, ':teacher_id' => $teacherId]);

    json_response(['date' => $date, 'teacherId' => $teacherId, 'records' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $data = request_data();
    $studentId = (int)($data['studentId'] ?? 0);
    $date = trim((string)($data['date'] ?? ''));
    $status = trim((string)($data['status'] ?? 'Pendente'));

    if ($studentId < 1 || !valid_date_value($date) || !valid_status($status)) {
        json_response(['error' => 'Dados invalidos.'], 422);
    }

    $allowedTeacherId = resolve_teacher_scope((int)($data['teacherId'] ?? 0));
    $check = $pdo->prepare('SELECT id FROM alunos WHERE id = :id AND teacher_id = :teacher_id AND is_active = 1');
    $check->execute([':id' => $studentId, ':teacher_id' => $allowedTeacherId]);
    if (!$check->fetch()) {
        json_response(['error' => 'Aluno nao pertence ao professor selecionado.'], 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO chamadas (student_id, attendance_date, status)
         VALUES (:student_id, :attendance_date, :status)
         ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':attendance_date' => $date,
        ':status' => $status,
    ]);

    json_response(['ok' => true]);
}

json_response(['error' => 'Metodo nao permitido.'], 405);
