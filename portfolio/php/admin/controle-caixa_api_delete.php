<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true || $_SESSION['tipo'] !== 'admin') {
    echo json_encode(['ok' => false, 'message' => 'Acesso negado.']);
    exit;
}

$mysqli = include __DIR__ . '/../banco/conectar.php';

if (!isset($_POST['id_temp']) || !ctype_digit($_POST['id_temp'])) {
    echo json_encode(['ok' => false, 'message' => 'ID inválido.']);
    exit;
}

$id = (int) $_POST['id_temp'];

$stmt = $mysqli->prepare("DELETE FROM temporary WHERE id_temp = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    if ($stmt->affected_rows > 0) {
        echo json_encode(['ok' => true, 'message' => 'Lançamento excluído com sucesso.']);
    } else {
        echo json_encode(['ok' => false, 'message' => 'Nenhum registro encontrado com esse ID.']);
    }
} else {
    echo json_encode(['ok' => false, 'message' => 'Erro ao excluir: ' . $mysqli->error]);
}

$stmt->close();
