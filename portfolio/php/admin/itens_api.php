<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

function responder(bool $ok, string $message, int $status = 200): never
{
    http_response_code($status);
    echo json_encode(['ok' => $ok, 'message' => $message], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || ($_SESSION['tipo'] ?? '') !== 'admin') {
    responder(false, 'Acesso não autorizado.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    responder(false, 'Método não permitido.', 405);
}

if (!hash_equals($_SESSION['csrf_itens'] ?? '', $_POST['csrf_token'] ?? '')) {
    responder(false, 'Sessão expirada. Atualize a página e tente novamente.', 419);
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$mysqli = include __DIR__ . '/../banco/conectar.php';
$categoriasPermitidas = require __DIR__ . '/categorias_reposicao.php';
$unidadesPermitidas = require __DIR__ . '/unidades_itens.php';
$acao = $_POST['acao'] ?? '';
$nome = trim($_POST['nome'] ?? '');
$unidade = trim($_POST['unidade'] ?? '');
$origem = trim($_POST['origem'] ?? '');
$idCat = filter_var($_POST['id_cat'] ?? null, FILTER_VALIDATE_INT);
$ativo = isset($_POST['ativo']) && (string) $_POST['ativo'] === '1' ? 1 : 0;

if (!in_array($acao, ['criar', 'editar'], true)) {
    responder(false, 'Ação inválida.', 422);
}

if ($nome === '' || mb_strlen($nome) > 100) {
    responder(false, 'Informe um nome válido para o item.', 422);
}

if (!array_key_exists($unidade, $unidadesPermitidas)) {
    responder(false, 'Selecione uma unidade de medida padronizada.', 422);
}

if (!in_array($origem, ['Fábrica', 'Loja'], true) || !$idCat) {
    responder(false, 'Informe uma origem e uma categoria válidas.', 422);
}

$categoria = $mysqli->prepare('SELECT ID_CAT, categoria FROM CATEGORIA WHERE ID_CAT = ?');
$categoria->bind_param('i', $idCat);
$categoria->execute();
$categoriaEncontrada = $categoria->get_result()->fetch_assoc();
if (!$categoriaEncontrada) {
    responder(false, 'Categoria não encontrada.', 422);
}
if (!in_array($categoriaEncontrada['categoria'], $categoriasPermitidas[$origem] ?? [], true)) {
    responder(false, "A categoria '{$categoriaEncontrada['categoria']}' não pertence ao destino {$origem}.", 422);
}

if ($acao === 'criar') {
    $duplicado = $mysqli->prepare('SELECT ID_ITM FROM ITENS WHERE LOWER(nome) = LOWER(?) AND origem = ? LIMIT 1');
    $duplicado->bind_param('ss', $nome, $origem);
    $duplicado->execute();
    if ($duplicado->get_result()->num_rows > 0) {
        responder(false, 'Já existe um item com esse nome e origem.', 409);
    }

    $stmt = $mysqli->prepare('INSERT INTO ITENS (ID_CAT, nome, unidade, origem, ativo) VALUES (?, ?, ?, ?, ?)');
    $stmt->bind_param('isssi', $idCat, $nome, $unidade, $origem, $ativo);
    if (!$stmt->execute()) {
        responder(false, 'Não foi possível cadastrar o item.', 500);
    }
    responder(true, 'Item cadastrado com sucesso.');
}

$idItem = filter_var($_POST['id_itm'] ?? null, FILTER_VALIDATE_INT);
if (!$idItem) {
    responder(false, 'Item inválido.', 422);
}

$duplicado = $mysqli->prepare('SELECT ID_ITM FROM ITENS WHERE LOWER(nome) = LOWER(?) AND origem = ? AND ID_ITM <> ? LIMIT 1');
$duplicado->bind_param('ssi', $nome, $origem, $idItem);
$duplicado->execute();
if ($duplicado->get_result()->num_rows > 0) {
    responder(false, 'Já existe outro item com esse nome e origem.', 409);
}

$stmt = $mysqli->prepare('UPDATE ITENS SET ID_CAT = ?, nome = ?, unidade = ?, origem = ?, ativo = ? WHERE ID_ITM = ?');
$stmt->bind_param('isssii', $idCat, $nome, $unidade, $origem, $ativo, $idItem);
if (!$stmt->execute() || $stmt->affected_rows < 0) {
    responder(false, 'Não foi possível atualizar o item.', 500);
}

responder(true, 'Item atualizado. O histórico de pedidos foi preservado.');
