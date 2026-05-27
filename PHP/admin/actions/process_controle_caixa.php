<?php
session_start();

$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');


$nome = $_POST['nome'] ?? '';
$tipo = $_POST['tipo'] ?? '';
$val = $_POST['valor'] ?? '';
$data_caixa = date('Y-m-d');

if (!$nome || !$tipo) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

if($tipo === 'gasolina') {
    $des = 's1';
    $val = 30;
}else if($tipo === 'venda') {
    $des = 'v1';
}else if($tipo === 'saida') {
    $des = 's2';
}else if($tipo === 'entrada') {
    $des = 'e1';
}else {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

$descricao = $des . ' ' . $nome;
$valor = floatval(abs($val));


$sql = $mysqli->prepare("INSERT INTO temporary (tipo, valor, nome, data_caixa, descricao) VALUES (?, ?, ?, ?, ?)");
$sql->bind_param("sisss", $tipo, $valor, $nome, $data_caixa, $descricao);
$sql->execute();
if ($sql->affected_rows > 0) {
    echo json_encode(['ok' => true, 'message' => 'Lançamento adicionado com sucesso']);
} else {
    echo json_encode(['ok' => false, 'message' => 'Erro ao adicionar lançamento']);
}