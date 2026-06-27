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

function valid_time_value(string $time): bool
{
    return preg_match('/^\d{2}:\d{2}$/', $time) === 1;
}

if ($method === 'GET') {
    $teacherId = resolve_teacher_scope((int)($_GET['teacherId'] ?? 0));
    $start = trim((string)($_GET['start'] ?? ''));
    $end = trim((string)($_GET['end'] ?? ''));

    if ($start !== '' || $end !== '') {
        if (!valid_date_value($start) || !valid_date_value($end) || $start > $end) {
            json_response(['error' => 'Periodo invalido.'], 422);
        }

        $stmt = $pdo->prepare(
            'SELECT alunos.id AS studentId, alunos.teacher_id AS teacherId,
                    chamadas.attendance_date AS attendanceDate,
                    chamadas.status,
                    chamadas.replacement_date AS replacementDate,
                    TIME_FORMAT(chamadas.replacement_time, "%H:%i") AS replacementTime,
                    chamadas.id AS attendanceId
             FROM chamadas
             INNER JOIN alunos ON alunos.id = chamadas.student_id
             WHERE alunos.teacher_id = :teacher_id
               AND chamadas.attendance_date BETWEEN :start_date AND :end_date
             ORDER BY chamadas.attendance_date ASC, alunos.class_time ASC'
        );
        $stmt->execute([
            ':teacher_id' => $teacherId,
            ':start_date' => $start,
            ':end_date' => $end,
        ]);

        json_response(['start' => $start, 'end' => $end, 'teacherId' => $teacherId, 'records' => $stmt->fetchAll()]);
    }

    $date = trim((string)($_GET['date'] ?? date('Y-m-d')));

    if (!valid_date_value($date)) {
        json_response(['error' => 'Data invalida.'], 422);
    }

    $stmt = $pdo->prepare(
        'SELECT alunos.id AS studentId, alunos.teacher_id AS teacherId,
                access_users.username AS teacherName, alunos.name, alunos.phone,
                alunos.class_day AS classDay,
                TIME_FORMAT(alunos.class_time, "%H:%i") AS classTime,
                COALESCE(chamadas.status, \'Pendente\') AS status,
                chamadas.replacement_date AS replacementDate,
                TIME_FORMAT(chamadas.replacement_time, "%H:%i") AS replacementTime,
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
    $replacementDate = trim((string)($data['replacementDate'] ?? ''));
    $replacementTime = trim((string)($data['replacementTime'] ?? ''));

    if ($studentId < 1 || !valid_date_value($date) || !valid_status($status)) {
        json_response(['error' => 'Dados invalidos.'], 422);
    }

    if ($replacementDate !== '' && !valid_date_value($replacementDate)) {
        json_response(['error' => 'Data da reposicao invalida.'], 422);
    }

    if ($replacementTime !== '' && !valid_time_value($replacementTime)) {
        json_response(['error' => 'Horario da reposicao invalido.'], 422);
    }

    if ($status !== 'Reposicao') {
        $replacementDate = '';
        $replacementTime = '';
    }

    $allowedTeacherId = resolve_teacher_scope((int)($data['teacherId'] ?? 0));
    $check = $pdo->prepare('SELECT id FROM alunos WHERE id = :id AND teacher_id = :teacher_id AND is_active = 1');
    $check->execute([':id' => $studentId, ':teacher_id' => $allowedTeacherId]);
    if (!$check->fetch()) {
        json_response(['error' => 'Aluno nao pertence ao professor selecionado.'], 403);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO chamadas (student_id, attendance_date, status, replacement_date, replacement_time)
         VALUES (:student_id, :attendance_date, :status, :replacement_date, :replacement_time)
         ON DUPLICATE KEY UPDATE
           status = VALUES(status),
           replacement_date = VALUES(replacement_date),
           replacement_time = VALUES(replacement_time),
           updated_at = CURRENT_TIMESTAMP'
    );
    $stmt->execute([
        ':student_id' => $studentId,
        ':attendance_date' => $date,
        ':status' => $status,
        ':replacement_date' => $replacementDate !== '' ? $replacementDate : null,
        ':replacement_time' => $replacementTime !== '' ? $replacementTime . ':00' : null,
    ]);

    json_response(['ok' => true]);
}

json_response(['error' => 'Metodo nao permitido.'], 405);
