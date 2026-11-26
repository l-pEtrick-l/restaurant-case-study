<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, string $message, int $status = 200, array $extra = []): never
{
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok, 'message' => $message], $extra), JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || ($_SESSION['tipo'] ?? '') !== 'admin') {
    responder(false, 'Acesso não autorizado.', 401);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método não permitido.', 405);
}
if (!hash_equals($_SESSION['csrf_solicitacoes'] ?? '', $_POST['csrf_token'] ?? '')) {
    responder(false, 'Sessão expirada. Atualize a página e tente novamente.', 419);
}

$idPedido = filter_var($_POST['pedido_id'] ?? null, FILTER_VALIDATE_INT);
if (!$idPedido) {
    responder(false, 'Solicitação inválida.', 422);
}

try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = include __DIR__ . '/../banco/conectar.php';

    $consulta = $mysqli->prepare('SELECT condicao FROM PED_REP WHERE ID_PR = ?');
    $consulta->bind_param('i', $idPedido);
    $consulta->execute();
    $pedido = $consulta->get_result()->fetch_assoc();

    if (!$pedido) {
        responder(false, 'Solicitação não encontrada.', 404);
    }
    if (mb_strtolower(trim($pedido['condicao'])) === 'finalizado') {
        responder(true, 'Esta solicitação já estava finalizada.', 200, [
            'pedido_id' => $idPedido,
            'condicao' => 'Finalizado',
            'alterado' => false,
        ]);
    }

    $condicao = 'Finalizado';
    $atualizacao = $mysqli->prepare('UPDATE PED_REP SET condicao = ? WHERE ID_PR = ?');
    $atualizacao->bind_param('si', $condicao, $idPedido);
    $atualizacao->execute();

    responder(true, 'Solicitação marcada como finalizada.', 200, [
        'pedido_id' => $idPedido,
        'condicao' => $condicao,
        'alterado' => true,
    ]);
} catch (Throwable $erro) {
    error_log(sprintf('[solicitacoes] %s #%d: %s', get_class($erro), $erro->getCode(), $erro->getMessage()));
    responder(false, 'Não foi possível finalizar a solicitação. Tente novamente; se o erro continuar, informe o código SOL-500.', 500, [
        'codigo' => 'SOL-500',
    ]);
}
