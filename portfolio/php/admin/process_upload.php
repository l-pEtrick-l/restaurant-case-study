<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php';
$mysqli = include __DIR__ . '/../banco/conectar.php';
$mysqli->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;


header('Content-Type: application/json; charset=UTF-8');

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
error_reporting(E_ALL);
$data = json_decode(file_get_contents('php://input'), true);

use PhpOffice\PhpSpreadsheet\IOFactory;

$cx = $mysqli->prepare("SELECT 1 FROM `temporary`");
$cx->execute();
if ($cx->get_result()->num_rows > 0) {
    echo json_encode(['ok' => false, 'message' => 'Enviar caixa primeiro']);
    exit;
}


    $stmt = $mysqli->prepare("SELECT 1 FROM pagmot WHERE dat = ?");
$stmt->bind_param("s", $data_fechamento);
$stmt->execute();
$chk = $stmt->get_result();
$stmt->close();


if ($chk->num_rows > 0) {
    echo json_encode(['ok' => false, 'message' => 'Formulário já se encontra enviado para esta data']);
    exit;
}



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

$mesas = $_POST['mesas'] ?? null;
$caixa_final = $_POST['caixa_final'] ?? null;
$data_fechamentoo = date('Y-m-d');

$data_fechamento = null;

for ($i = 1; $i < count($rows); $i++) {
    $excelDate = $rows[$i][9] ?? null;
    if (!empty($excelDate)) {
        if (is_numeric($excelDate)) {
            $data_fechamento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d');
        } else {
            $dateObj = DateTime::createFromFormat('d/m/Y', $excelDate);
            $data_fechamento = $dateObj ? $dateObj->format('Y-m-d') : null;
        }
        break; // já achou a data, não precisa continuar
    }
}

// Se não tiver data, erro
if (!$data_fechamento) {
    echo json_encode(['ok' => false, 'message' => 'Nenhuma data encontrada no arquivo.']);
    exit;
}

// Verifica se já foi enviada
$stmt = $mysqli->prepare("SELECT 1 FROM excel_data WHERE data_fechamento = ?");
$stmt->bind_param("s", $data_fechamento);
$stmt->execute();
$jafoi = $stmt->get_result();
$stmt->close();

if ($jafoi->num_rows > 0) {
    echo json_encode(['ok' => false, 'message' => 'Formulário já foi enviado para esta data.']);
    exit;
}

$stmt = $mysqli->prepare("INSERT INTO fechamento (data_fechamento) VALUES (?)
    ON DUPLICATE KEY UPDATE fechamento_id = LAST_INSERT_ID(fechamento_id)");
$stmt->bind_param("s", $data_fechamentoo);
$stmt->execute();
$fechamento_id = $mysqli->insert_id;
$stmt->close();


$stmt = $mysqli->prepare("INSERT INTO `manual` (mesas, caixa_final, fechamento_id) VALUES (?, ?, ?)");
$stmt->bind_param("ids", $mesas, $caixa_final, $fechamento_id);
$stmt->execute();
$stmt->close();


for ($i = 1; $i < count($rows); $i++) { // Começa da linha 2
    $NDP = $rows[$i][0] ?? null;
    $origem = $rows[$i][1] ?? null;
    $CDP = $rows[$i][2] ?? null;
    $CU = $rows[$i][3] ?? null;
    $MDS = $rows[$i][4] ?? null;
    $descontos = is_numeric($rows[$i][5]) ? floatval($rows[$i][5]) : null;
    $VF = is_numeric($rows[$i][6]) ? floatval($rows[$i][6]) : null;
    $VT = is_numeric($rows[$i][8]) ? floatval($rows[$i][8]) : null;


    // 🔹 Conversão correta da data
    $excelDate = $rows[$i][9] ?? null;
    if (!empty($excelDate)) {
        if (is_numeric($excelDate)) {
            // Caso seja número serial do Excel
            $data_fechamento = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate)->format('Y-m-d');
        } else {
            // Caso seja string no formato dd/mm/yyyy
            $dateObj = DateTime::createFromFormat('d/m/Y', $excelDate);
            $data_fechamento = $dateObj ? $dateObj->format('Y-m-d') : null;
        }
    } else {
        $data_fechamento = null;
    }

    $entregador = $rows[$i][10] ?? null;
    $status = $rows[$i][11] ?? null;



    $stmt = $mysqli->prepare("
        INSERT INTO excel_data
        (NDP, origem, CDP, CU, MDS, descontos, VF, VT, data_fechamento, entregador, fechamento_id, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssssdddssis", $NDP, $origem, $CDP, $CU, $MDS, $descontos, $VF, $VT, $data_fechamento, $entregador, $fechamento_id, $status);
    $stmt->execute();
    $stmt->close();
}



////////////////////////////////////////////////////////////////////////////////

    try {
    $mysqli->begin_transaction();

    $gasolina = $saida = $venda = $entrada = [];

    $stm = $mysqli->prepare("
        SELECT LOWER(observacao), valor
        FROM excel_caixa
        WHERE data_caixa = ?
    ");
    $stm->bind_param("s", $data_fechamento);
    $stm->execute();
    $res = $stm->get_result();

    while ($r = $res->fetch_row()) {
        [$obs, $val] = $r;

        if (str_starts_with($obs, 's1 ')) $gasolina[substr($obs,3)] = ($gasolina[substr($obs,3)] ?? 0) + $val;
        if (str_starts_with($obs, 's2 ')) $saida[substr($obs,3)]    = ($saida[substr($obs,3)] ?? 0) + abs($val);
        if (str_starts_with($obs, 'v1 ')) $venda[substr($obs,3)]    = ($venda[substr($obs,3)] ?? 0) + abs($val);
        if (str_starts_with($obs, 'e1 ')) $entrada[substr($obs,3)]  = ($entrada[substr($obs,3)] ?? 0) + $val;
    }
    $stm->close();

    error_log('5 blz');

    /* =========================
       6) FRETE
    ========================= */
    $rule = $mysqli->query("SELECT taxa_ifood_zerada FROM rules ORDER BY rules_id DESC LIMIT 1")
                   ->fetch_assoc();

    $taxa_ifood = $rule['taxa_ifood_zerada'] ?? 0;

    $frete = [];
    $stm = $mysqli->prepare("
        SELECT LOWER(TRIM(entregador)), SUM(CASE WHEN vf = 0 THEN ? ELSE vf END)
        FROM excel_data
        WHERE data_fechamento = ? AND entregador IS NOT NULL
        AND entregador <> ''
        GROUP BY entregador
    ");
    $stm->bind_param("ds", $taxa_ifood, $data_fechamento);
    $stm->execute();
    $res = $stm->get_result();

    while ($r = $res->fetch_row()) {
        $frete[$r[0]] = $r[1];
    }
    $stm->close();

    error_log('6 blz');

    /* =========================
       7) INSERT pagmot
    ========================= */
    $motoboys = array_unique(array_merge(
        array_keys($gasolina),
        array_keys($saida),
        array_keys($venda),
        array_keys($entrada),
        array_keys($frete)
    ));

    $ins = $mysqli->prepare("
        INSERT INTO pagmot
        (usuario_id, nome, taxas, saidas, entradas, dat, gasolina, diaria)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($motoboys as $nomeKey) {

        $id = null;
        $q = $mysqli->prepare("SELECT usuario_id FROM motoboy WHERE LOWER(TRIM(nome)) = ?");
        $q->bind_param("s", $nomeKey);
        $q->execute();
        $q->bind_result($id);
        $q->fetch();
        $q->close();

        if (!$id) {

            echo json_encode(['ok' => false, 'message' => "Motoboy não cadastrado: $nomeKey"]);
    exit;
        }



        $valorSaidas  = ($venda[$nomeKey] ?? 0) + ($saida[$nomeKey] ?? 0);
        $valorEntrada = $entrada[$nomeKey] ?? 0;
        $fre          = $frete[$nomeKey] ?? 0;
        $valorFrete   = ($fre < 40) ? 40 : $fre;
        $gasStatus    = (abs(($gasolina[$nomeKey] ?? 0)) == 30) ? 'pago' : 'nao pago';
        $diaria = "nao pago";
 error_log("antes 1");
        error_log(json_encode(compact(
            'id','nomeKey','valorFrete','valorSaidas','valorEntrada','gasStatus'
        ), JSON_UNESCAPED_UNICODE));

        error_log("depois 1");
        $ins->bind_param(
            "isdddsss",
            $id,
            $nomeKey,
            $valorFrete,
            $valorSaidas,
            $valorEntrada,
            $data_fechamento,
            $gasStatus,
            $diaria
        );
          error_log("depois 2");
        $ins->execute();
    }
  error_log("depois 3");
    $ins->close();
    $mysqli->commit();

    error_log('COMMIT OK');

} catch (Exception $e) {
    $mysqli->rollback();
    error_log('ROLLBACK: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}

echo json_encode(['ok' => true, 'message' => 'Arquivo enviado com sucesso.']);
