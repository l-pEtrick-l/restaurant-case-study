<?php
session_start();

require __DIR__ . '/../../vendor/autoload.php';
$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');

/* =========================
   CONFIG DEBUG
========================= */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/debug.log');
error_reporting(E_ALL);

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Csv;

/* =========================
   1) RECEBER ARQUIVO
=========================
if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Arquivo inválido']);
    exit;
}

$ext = strtolower(pathinfo($_FILES['excel_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Formato não suportado']);
    exit;
}

$tmpPath = $_FILES['excel_file']['tmp_name'];

if ($ext === 'csv') {
    $reader = new Csv();
    $reader->setDelimiter(';');
    $reader->setInputEncoding('UTF-8');
    $spreadsheet = $reader->load($tmpPath);
} else {
    $spreadsheet = IOFactory::load($tmpPath);
}

$rows = $spreadsheet->getSheet(0)->toArray();
error_log('1 blz');

 =========================
   2) EXCEL → ARRAY
=========================
$dados = [];
$data_fechamento = null;

for ($i = 1; $i < count($rows); $i++) {

    $valorRaw = $rows[$i][6] ?? null;
    if ($valorRaw) {
        $valorRaw = str_replace(['R$', ' '], '', $valorRaw);
        $valorRaw = str_replace(['.', ','], ['', '.'], $valorRaw);
        $valor = is_numeric($valorRaw) ? (float)$valorRaw : 0;
    } else {
        $valor = 0;
    }

    $excelDate = $rows[$i][5] ?? null;
    if ($excelDate) {
        if (is_numeric($excelDate)) {
            $dateObj = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($excelDate);
        } else {
            $dateObj = DateTime::createFromFormat('d/m/Y', trim($excelDate))
                   ?: DateTime::createFromFormat('d/m/Y H:i', trim($excelDate));
        }
        $data_fechamento = $dateObj ? $dateObj->format('Y-m-d') : null;
    }

    $dados[] = [
        'codigo' => is_numeric($rows[$i][0]) ? $rows[$i][0] : null,
        'loja'   => $rows[$i][1] ?? null,
        'tipo'   => $rows[$i][2] ?? null,
        'desc'   => $rows[$i][3] ?? null,
        'obs'    => strtolower(trim($rows[$i][4] ?? '')),
        'data'   => $data_fechamento,
        'valor'  => $valor
    ];
}

if (!$data_fechamento) {
    echo json_encode(['ok' => false, 'message' => 'Data inválida no arquivo']);
    exit;
}

error_log('2 blz'); */

/* =========================
   3) TRANSAÇÃO
========================= */


    error_log('3 blz');

    /* =========================
       4) INSERT excel_caixa
    =========================
    $ins = $mysqli->prepare("
        INSERT INTO excel_caixa
        (codigo, loja, tipo, descricao, observacao, data_caixa, valor)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($dados as $d) {
        $ins->bind_param(
            "ssssssd",
            $d['codigo'],
            $d['loja'],
            $d['tipo'],
            $d['desc'],
            $d['obs'],
            $d['data'],
            $d['valor']
        );
        $ins->execute();
    }
    $ins->close();

    error_log('4 blz');

    =========================
       5) RESUMOS
    ========================= */
  $stmt = $mysqli->prepare("
    SELECT data_caixa
    FROM excel_caixa
    ORDER BY data_caixa DESC
    LIMIT 1
");
$stmt->execute();
$stmt->bind_result($data_fechamento);
$stmt->fetch();
$stmt->close();

if (!$data_fechamento) {
    throw new Exception("Nenhuma data encontrada em excel_caixa");
}

    try {
    $mysqli->begin_transaction();

    $chk = $mysqli->prepare("SELECT 1 FROM pagmot WHERE dat = ?");
    $chk->bind_param("s", $data_fechamento);
    $chk->execute();
    if ($chk->get_result()->num_rows > 0) {
        throw new Exception('Formulário já enviado para esta data');
    }
    $chk->close();

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
            throw new Exception("Motoboy não cadastrado: $nomeKey");
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
    echo json_encode(['ok' => true]);

} catch (Exception $e) {
    $mysqli->rollback();
    error_log('ROLLBACK: ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
