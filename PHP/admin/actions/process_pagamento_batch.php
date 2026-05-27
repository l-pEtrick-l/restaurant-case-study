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

$stmt = $mysqli->prepare("UPDATE pagmot 
    SET taxas = ?, saidas = ?, entradas = ?, gasolina = ?, diaria = ?
    WHERE nome = ? AND dat = ?");

foreach ($data as $linha) {
    $taxas = floatval($linha['taxas']);
    $saidas = floatval($linha['saidas']);
    $entradas = floatval($linha['entradas']);
    $gasolina = $linha['gasolina'];
    $diaria = $linha['diaria'];
    $motoboy = $linha['motoboy'];
    $data_linha = $linha['data_linha'];

    $stmt->bind_param("dddssss", $taxas, $saidas, $entradas, $gasolina, $diaria, $motoboy, $data_linha);
    $stmt->execute();
}

echo json_encode(['ok' => true, 'message' => 'Todas as alterações foram salvas com sucesso!']);
