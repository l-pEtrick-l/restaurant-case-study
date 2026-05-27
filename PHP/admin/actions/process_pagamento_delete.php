<?php
session_start();
$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');

$data_linha = $_POST['data_linha'] ?? null;
$motoboy = $_POST['motoboy'] ?? null;

if (!$data_linha || !$motoboy) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

$stmt = $mysqli->prepare(
    "DELETE FROM pagmot WHERE dat = ? AND nome = ?"
);
$stmt->bind_param("ss", $data_linha, $motoboy);
$stmt->execute();

echo json_encode(['ok' => true, 'message' => 'Lançamento excluído com sucesso']);
