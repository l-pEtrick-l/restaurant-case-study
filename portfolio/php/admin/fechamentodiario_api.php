<?php
session_start();
$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

$stmt = $mysqli->prepare("UPDATE `manual`
    SET caixa_final = ?, mesas = ?, fechamento_id = ?");

foreach ($data as $linha) {
    $caixa_final = floatval($linha['caixa_final']);
    $mesas = floatval($linha['mesas']);
    $fechamento_id = intval($linha['fechamento_id']);

    $stmt->bind_param("ddi", $caixa_final, $mesas, $fechamento_id);
    $stmt->execute();
}

echo json_encode(['ok' => true, 'message' => 'Todas as alterações foram salvas com sucesso!']);
