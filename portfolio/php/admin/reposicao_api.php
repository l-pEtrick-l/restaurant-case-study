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
if (!hash_equals($_SESSION['csrf_reposicao'] ?? '', $_POST['csrf_token'] ?? '')) {
    responder(false, 'Sessão expirada. Atualize a página e tente novamente.', 419);
}

$usuarioId = filter_var($_SESSION['usuario_id'] ?? null, FILTER_VALIDATE_INT);
$observacao = trim($_POST['observacao_pedido'] ?? '');
$itensRecebidos = json_decode($_POST['itens'] ?? '[]', true);
if (!$usuarioId || !is_array($itensRecebidos) || count($itensRecebidos) === 0) {
    responder(false, 'Adicione pelo menos um item ao pedido.', 422);
}
if (mb_strlen($observacao) > 100) {
    responder(false, 'A observação geral deve ter no máximo 100 caracteres.', 422);
}

$itens = [];
foreach ($itensRecebidos as $item) {
    $id = filter_var($item['id'] ?? null, FILTER_VALIDATE_INT);
    $quantidadeBruta = str_replace(',', '.', (string) ($item['quantidade'] ?? ''));
    $quantidade = is_numeric($quantidadeBruta) ? round((float) $quantidadeBruta, 3) : 0;
    $obs = trim($item['observacao'] ?? '');
    if (!$id || $quantidade <= 0 || $quantidade > 999999.999 || mb_strlen($obs) > 100) {
        responder(false, 'Há um item com quantidade ou observação inválida.', 422);
    }
    $itens[$id] = ['quantidade' => $quantidade, 'observacao' => $obs];
}

$transacaoIniciada = false;
try {
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    $mysqli = include __DIR__ . '/../banco/conectar.php';
    $unidadesPermitidas = require __DIR__ . '/unidades_itens.php';
    $ids = array_keys($itens);
    $listaIds = implode(',', array_map('intval', $ids));
    $resultado = $mysqli->query("SELECT ID_ITM, unidade FROM ITENS WHERE ativo = 1 AND ID_ITM IN ($listaIds)");
    $idsValidos = [];
    while ($row = $resultado->fetch_assoc()) {
        $idValido = (int) $row['ID_ITM'];
        $idsValidos[] = $idValido;
        $unidade = mb_strtolower(trim($row['unidade']));
        $permiteFracao = (bool) ($unidadesPermitidas[$unidade]['permite_fracao'] ?? false);
        if (!$permiteFracao && abs($itens[$idValido]['quantidade'] - round($itens[$idValido]['quantidade'])) > 0.000001) {
            responder(false, "A unidade '{$row['unidade']}' aceita apenas quantidades inteiras.", 422);
        }
    }
    if (count($idsValidos) !== count($ids)) {
        responder(false, 'Um ou mais itens não existem ou estão inativos. Atualize a página.', 409);
    }

    $mysqli->begin_transaction();
    $transacaoIniciada = true;
    $condicao = 'Solicitado';
    $observacaoPedido = $observacao !== '' ? $observacao : null;
    $pedido = $mysqli->prepare('INSERT INTO PED_REP (usuario_id, condicao, data_PR, observacao) VALUES (?, ?, NOW(), ?)');
    $pedido->bind_param('iss', $usuarioId, $condicao, $observacaoPedido);
    $pedido->execute();

    $pedidoId = (int) $mysqli->insert_id;
    $pedidoItem = $mysqli->prepare('INSERT INTO PED_ITM (ID_PR, ID_ITM, quantidade, observacao) VALUES (?, ?, ?, ?)');
    foreach ($itens as $idItem => $dados) {
        $idItem = (int) $idItem;
        $quantidadeItem = (float) $dados['quantidade'];
        $observacaoItem = $dados['observacao'] !== '' ? $dados['observacao'] : null;
        $pedidoItem->bind_param('iids', $pedidoId, $idItem, $quantidadeItem, $observacaoItem);
        $pedidoItem->execute();
    }
    $mysqli->commit();
    $transacaoIniciada = false;
    unset($_SESSION['csrf_reposicao']);
    responder(true, 'Solicitação de reposição enviada com sucesso.', 201, ['pedido_id' => $pedidoId]);
} catch (Throwable $erro) {
    if ($transacaoIniciada) {
        try {
            $mysqli->rollback();
        } catch (Throwable $rollbackErro) {
            error_log('[reposicao] Falha no rollback: ' . $rollbackErro->getMessage());
        }
    }
    error_log(sprintf('[reposicao] %s #%d: %s', get_class($erro), $erro->getCode(), $erro->getMessage()));
    responder(false, 'Não foi possível registrar o pedido. Tente novamente; se o erro continuar, informe o código REP-500.', 500, ['codigo' => 'REP-500']);
}
