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
        json_response(['error' => 'Nao autenticado.'], 401);
    }
}

function require_access_level(int $minimumLevel): void
{
    require_auth();

    $level = (int)($_SESSION['teacher']['accessLevel'] ?? 0);
    if ($level < $minimumLevel) {
        json_response(['error' => 'Acesso permitido apenas para nivel ' . $minimumLevel . '.'], 403);
    }
}

function valid_status(string $status): bool
{
    return in_array($status, ['Presente', 'Pendente', 'Ausente'], true);
}
