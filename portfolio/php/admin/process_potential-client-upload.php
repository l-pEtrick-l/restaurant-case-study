<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL & ~E_DEPRECATED);

require __DIR__ . '/../../vendor/autoload.php';
$mysqli = include __DIR__ . '/../banco/conectar.php';
$mysqli->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;


header('Content-Type: application/json; charset=UTF-8');

$data = json_decode(file_get_contents('php://input'), true);

use PhpOffice\PhpSpreadsheet\IOFactory;

// 1) Receber arquivo
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados inválidos']);
    exit;
}

$tmpPath = $_FILES['excel_file']['tmp_name'];
$spreadsheet = IOFactory::load($tmpPath);
$sheet = $spreadsheet->getSheet(0);
$rows = $sheet->toArray();


for ($i = 1; $i < count($rows); $i++) { // Começa da linha 2
    $nome_cliente = $rows[$i][0] ?? null;
    $numero_telefone = $rows[$i][1] ?? null;
    $numero_whatsapp = $rows[$i][2] ?? null;
    $quant_pedidos = is_numeric($rows[$i][3]) ? floatval($rows[$i][3]) : null;
    $data_insercao = date('Y-m-d');

    $stmt = $mysqli->prepare("INSERT INTO excel_client_potential (nome_cliente, numero_telefone, numero_whatsapp, quant_pedidos, data_insercao) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param('sssis', $nome_cliente, $numero_telefone, $numero_whatsapp, $quant_pedidos, $data_insercao);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['ok' => true, 'message' => 'Arquivo enviado com sucesso.']);
