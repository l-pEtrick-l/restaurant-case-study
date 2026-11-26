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


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Requisição inválida']);
    exit;
}

$nome_campanha = $_POST['titulotext'] ?? null;
$msgzap        = $_POST['escopomsg'] ?? null;
$quantativo  = isset($_POST['quantidadeativo']) ? intval($_POST['quantidadeativo']) : 0;
$quantinativo  = isset($_POST['quantidadeinativo']) ? intval($_POST['quantidadeinativo']) : 0;
$quantpotencial  = isset($_POST['quantidadepotencial']) ? intval($_POST['quantidadepotencial']) : 0;
$imgzapPath    = null;

// validação básica
if (empty($nome_campanha) || empty($msgzap)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Preencha todos os campos obrigatórios']);
    exit;
}

if (isset($_POST['Clients']) && is_array($_POST['Clients'])) {
    $clientes = implode('/', $_POST['Clients']) . '/';
} else {
    $clientes = '';
}


$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_active
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_active)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_ativos = $row['total'];
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_inactive
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_inactive)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_inativos = $row['total']; // <-- variável certa
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_potential
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_potential)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_potencial = $row['total']; // <-- variável certa
$stmt->close();


    $quantidade = $quantativo + $quantinativo + $quantpotencial;

    if ($quantidade !== null) {
        if ($quantativo > $quantidade_clientes_ativos || $quantinativo > $quantidade_clientes_inativos || $quantpotencial > $quantidade_clientes_potencial) {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'A quantidade de clientes selecionados excede o número disponível.']);
            exit;
        }
    }


// upload da imagem
if (isset($_FILES['imgzap']) && $_FILES['imgzap']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/../../uploads/whatsapp_img/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

    $ext = pathinfo($_FILES['imgzap']['name'], PATHINFO_EXTENSION);
    $fileName = uniqid('img_', true) . '.' . $ext;

    $imgzapPath = 'uploads/whatsapp_img/' . $fileName;
    move_uploaded_file($_FILES['imgzap']['tmp_name'], $uploadDir . $fileName);
}

// grava no banco
$stmt = $mysqli->prepare("
    INSERT INTO clients (campanha, mensagem, imagem, clientes_da_campanha, quantidade_active_clients, quantidade_inactive_clients, quantidade_potential_clients)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
$stmt->bind_param('ssssiii', $nome_campanha, $msgzap, $imgzapPath, $clientes, $quantativo, $quantinativo, $quantpotencial);
$stmt->execute();
$stmt->close();

echo json_encode(['ok' => true, 'message' => 'Campanha criada com sucesso']);
exit;
?>
