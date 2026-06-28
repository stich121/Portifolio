<?php
declare(strict_types=1);

session_start();

function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function request_data(): array
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?: '[]', true);

    return is_array($data) ? $data : [];
}

function require_auth(): void
{
    if (empty($_SESSION['presence_authenticated'])) {
        json_response(['error' => 'Não autenticado.'], 401);
    }
}

function current_teacher_id(): int
{
    return (int)($_SESSION['teacher']['id'] ?? 0);
}

function current_teacher_level(): int
{
    return (int)($_SESSION['teacher']['accessLevel'] ?? 0);
}

function require_access_level(int $minimumLevel): void
{
    require_auth();

    if (current_teacher_level() < $minimumLevel) {
        json_response(['error' => 'Acesso permitido apenas para nível ' . $minimumLevel . '.'], 403);
    }
}

function resolve_teacher_scope(int $requestedTeacherId = 0): int
{
    require_auth();

    $ownTeacherId = current_teacher_id();
    if ($ownTeacherId < 1) {
        json_response(['error' => 'Professor da sessão inválido.'], 401);
    }

    if (current_teacher_level() === 3 && $requestedTeacherId > 0) {
        return $requestedTeacherId;
    }

    return $ownTeacherId;
}

function valid_status(string $status): bool
{
    return in_array($status, ['Presente', 'Pendente', 'Ausente', 'Reposicao'], true);
}

