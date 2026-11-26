<?php
require_once __DIR__ . '/../config/ambiente.php';
if (restaurantEnv('RESTAURANT_SMTP_USER') === '' || restaurantEnv('RESTAURANT_SMTP_PASSWORD') === '') {
    http_response_code(503);
    exit('Integração de e-mail não configurada.');
}
$logoEmail = htmlspecialchars(restaurantEnv('RESTAURANT_MAIL_LOGO_URL'), ENT_QUOTES, 'UTF-8');

require_once '../../vendor/autoload.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

$mysqli = include __DIR__ . '/../banco/conectar.php';
$mysqli->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
$mysqli->set_charset("utf8mb4");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


// =====================================================
// PHPMailer
// =====================================================

$mail = new PHPMailer(true);

$mail->CharSet = 'UTF-8';
$mail->Encoding = 'base64';


// =====================================================
// VALOR DA GASOLINA
// =====================================================

$stm = $mysqli->prepare("
    SELECT valor_gasolina
    FROM rules
");

$stm->execute();

$valor_gasolina = $stm
    ->get_result()
    ->fetch_assoc()['valor_gasolina'];

$stm->close();


// =====================================================
// PERÍODO DO RELATÓRIO
// =====================================================

$data_inicio = date('d/m', strtotime('-7 days'));
$data_fim = date('d/m');


// =====================================================
// PAGAMENTOS DOS MOTObOYS
// =====================================================

$stm = $mysqli->prepare("
SELECT
    nome,
        SUM(taxas) AS taxas,
        SUM(saidas) AS saidas,
        SUM(entradas) AS entradas,
        SUM(taxas + entradas - saidas + ?) AS Total
    FROM pagmot
    WHERE dat >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        AND dat <= CURDATE()
        AND nome <> 'mercado'
        AND diaria <> 'pago'
    GROUP BY nome
    ORDER BY nome ASC
");

$stm->bind_param("d", $valor_gasolina);

$stm->execute();

$result = $stm->get_result();

$array = [];

while ($row = $result->fetch_assoc()) {
    $array[] = $row;
}

$stm->close();

$stm = $mysqli->prepare("
    SELECT u.email
    FROM admin a
    LEFT JOIN usuario u ON u.usuario_id = a.usuario_id
    WHERE a.cargo IN ('Dono', 'Dev', 'Administrativo')
");

$stm->execute();
$result = $stm->get_result();

$envio = [];

while ($row = $result->fetch_assoc()) {
    $envio[] = $row['email'];
}

$stm->close();


// =====================================================
// TOTAIS
// =====================================================

$total_taxas = array_sum(
    array_column($array, 'taxas')
);

$total_entradas = array_sum(
    array_column($array, 'entradas')
);

$total_saidas = array_sum(
    array_column($array, 'saidas')
);

$total_geral = array_sum(
    array_column($array, 'Total')
);


// =====================================================
// HTML DO E-MAIL
// =====================================================

$html = '

<!DOCTYPE html>

<html lang="pt-BR">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Relatório Semanal - 180 Food House</title>

</head>


<body style="
    margin:0;
    padding:0;
    background-color:#f4f5f7;
    font-family:Arial, Helvetica, sans-serif;
    color:#333333;
">


<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        background-color:#f4f5f7;
        padding:30px 10px;
    "
>

<tr>

<td align="center">


<!-- ================================================= -->
<!-- CONTAINER PRINCIPAL -->
<!-- ================================================= -->

<table
    width="700"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width:700px;
        max-width:100%;
        background-color:#ffffff;
        border-radius:12px;
        overflow:hidden;
    "
>


<!-- ================================================= -->
<!-- CABEÇALHO -->
<!-- ================================================= -->

<tr>

<td
    style="
        background-color:#111111;
        padding:30px;
        text-align:center;
    "
>


<!-- ESPAÇO PARA LOGO -->

<div style="
    width:140px;
    height:80px;
    margin:0 auto 20px auto;
    background-color:#222222;
    border-radius:8px;
    color:#aaaaaa;
    font-size:13px;
    line-height:80px;
">

    <img
    src="' . $logoEmail . '"
    width="200"
    style="display:block; margin:auto;"
><br><br>

</div>

<br><br>
<h1 style="
    margin:0;
    color:#ffffff;
    font-size:26px;
">

    180 Food House

</h1>


<p style="
    margin:8px 0 0 0;
    color:#bbbbbb;
    font-size:14px;
">

    Relatório de pagamento semanal

</p>


</td>

</tr>


<!-- ================================================= -->
<!-- PERÍODO -->
<!-- ================================================= -->

<tr>

<td
    style="
        padding:30px 30px 15px 30px;
    "
>


<p style="
    margin:0;
    color:#777777;
    font-size:13px;
">

    PERÍODO DO RELATÓRIO

</p>


<p style="
    margin:5px 0 0 0;
    font-size:22px;
    font-weight:bold;
    color:#222222;
">

    ' . $data_inicio . ' - ' . $data_fim . '

</p>


</td>

</tr>


<!-- ================================================= -->
<!-- RESUMO -->
<!-- ================================================= -->

<tr>

<td
    style="
        padding:15px 30px 25px 30px;
    "
>


<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
>


<tr>


<!-- REGISTROS -->

<td
    width="50%"
    style="
        padding-right:6px;
    "
>

<div style="
    background-color:#f7f7f7;
    border-radius:8px;
    padding:18px;
">


<p style="
    margin:0;
    color:#777777;
    font-size:12px;
">

    MOTOBOYS

</p>


<p style="
    margin:8px 0 0 0;
    font-size:24px;
    font-weight:bold;
    color:#111111;
">

    ' . count($array) . '

</p>


</div>

</td>


<!-- TOTAL -->

<td
    width="50%"
    style="
        padding-left:6px;
    "
>

<div style="
    background-color:#f7f7f7;
    border-radius:8px;
    padding:18px;
">


<p style="
    margin:0;
    color:#777777;
    font-size:12px;
">

    TOTAL

</p>


<p style="
    margin:8px 0 0 0;
    font-size:24px;
    font-weight:bold;
    color:#111111;
    white-space:nowrap;
">

    R$ ' . number_format($total_geral, 2, ',', '.') . '

</p>


</div>

</td>


</tr>

</table>

</td>

</tr>


<!-- ================================================= -->
<!-- TÍTULO -->
<!-- ================================================= -->

<tr>

<td
    style="
        padding:10px 30px;
    "
>


<h2 style="
    margin:0;
    font-size:18px;
    color:#222222;
">

    Detalhamento dos pagamentos

</h2>


</td>

</tr>


<!-- ================================================= -->
<!-- TABELA -->
<!-- ================================================= -->

<tr>

<td
    style="
        padding:10px 30px 30px 30px;
    "
>


<table
    width="100%"
    cellpadding="0"
    cellspacing="0"
    border="0"
    style="
        width:100%;
        border-collapse:collapse;
        table-layout:fixed;
    "
>


<!-- DEFINIÇÃO DAS COLUNAS -->

<colgroup>

    <col style="width:32%;">

    <col style="width:17%;">

    <col style="width:17%;">

    <col style="width:17%;">

    <col style="width:17%;">

</colgroup>


<!-- CABEÇALHO DA TABELA -->

<tr style="
    background-color:#111111;
">


<th
    style="
        width:32%;
        padding:12px 8px;
        color:#ffffff;
        text-align:left;
        font-size:12px;
        white-space:nowrap;
    "
>

    Motoboy

</th>


<th
    style="
        width:17%;
        padding:12px 8px;
        color:#ffffff;
        text-align:right;
        font-size:12px;
        white-space:nowrap;
    "
>

    Taxas

</th>


<th
    style="
        width:17%;
        padding:12px 8px;
        color:#ffffff;
        text-align:right;
        font-size:12px;
        white-space:nowrap;
    "
>

    Entradas

</th>


<th
    style="
        width:17%;
        padding:12px 8px;
        color:#ffffff;
        text-align:right;
        font-size:12px;
        white-space:nowrap;
    "
>

    Saídas

</th>


<th
    style="
        width:17%;
        padding:12px 8px;
        color:#ffffff;
        text-align:right;
        font-size:12px;
        white-space:nowrap;
    "
>

    Total

</th>


</tr>
';


// =====================================================
// LINHAS DOS MOTOBOYS
// =====================================================

foreach ($array as $row) {

    $html .= '

<tr
    style="
        border-bottom:1px solid #eeeeee;
    "
>


<!-- NOME -->

<td
    style="
        width:32%;
        padding:12px 8px;
        font-size:13px;
        font-weight:bold;
        color:#333333;
        text-align:left;
        white-space:nowrap;
    "
>

    ' . htmlspecialchars($row['nome'], ENT_QUOTES, 'UTF-8') . '

</td>


<!-- TAXAS -->

<td
    style="
        width:17%;
        padding:12px 8px;
        font-size:13px;
        color:#333333;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($row['taxas'], 2, ',', '.') . '

</td>


<!-- ENTRADAS -->

<td
    style="
        width:17%;
        padding:12px 8px;
        font-size:13px;
        color:#333333;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($row['entradas'], 2, ',', '.') . '

</td>


<!-- SAÍDAS -->

<td
    style="
        width:17%;
        padding:12px 8px;
        font-size:13px;
        color:#333333;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($row['saidas'], 2, ',', '.') . '

</td>


<!-- TOTAL -->

<td
    style="
        width:17%;
        padding:12px 8px;
        font-size:13px;
        font-weight:bold;
        color:#333333;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($row['Total'], 2, ',', '.') . '

</td>


</tr>

';
}


// =====================================================
// TOTAL FINAL
// =====================================================

$html .= '

<tr
    style="
        background-color:#f2f2f2;
        border-top:2px solid #111111;
    "
>


<!-- TOTAL -->

<td
    style="
        width:32%;
        padding:14px 8px;
        font-size:13px;
        font-weight:bold;
        color:#111111;
        text-align:left;
        white-space:nowrap;
    "
>

    TOTAL

</td>


<!-- TOTAL TAXAS -->

<td
    style="
        width:17%;
        padding:14px 8px;
        font-size:13px;
        font-weight:bold;
        color:#111111;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($total_taxas, 2, ',', '.') . '

</td>


<!-- TOTAL ENTRADAS -->

<td
    style="
        width:17%;
        padding:14px 8px;
        font-size:13px;
        font-weight:bold;
        color:#111111;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($total_entradas, 2, ',', '.') . '

</td>


<!-- TOTAL SAÍDAS -->

<td
    style="
        width:17%;
        padding:14px 8px;
        font-size:13px;
        font-weight:bold;
        color:#111111;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($total_saidas, 2, ',', '.') . '

</td>


<!-- TOTAL GERAL -->

<td
    style="
        width:17%;
        padding:14px 8px;
        font-size:13px;
        font-weight:bold;
        color:#111111;
        text-align:right;
        white-space:nowrap;
    "
>

    R$ ' . number_format($total_geral, 2, ',', '.') . '

</td>


</tr>


</table>


</td>

</tr>


<!-- ================================================= -->
<!-- RODAPÉ -->
<!-- ================================================= -->

<tr>

<td
    style="
        background-color:#f7f7f7;
        padding:20px 30px;
        text-align:center;
    "
>


<p style="
    margin:0;
    color:#999999;
    font-size:12px;
">

    Relatório gerado automaticamente pelo sistema 180 Food House.

</p>


<p style="
    margin:6px 0 0 0;
    color:#bbbbbb;
    font-size:11px;
">

    Este e-mail foi enviado automaticamente.

</p>


</td>

</tr>


</table>


</td>

</tr>

</table>


</body>

</html>

';


// =====================================================
// ENVIO DO E-MAIL
// =====================================================

try {

    $mail->isSMTP();

    $mail->Host = 'smtp.gmail.com';

    $mail->SMTPAuth = true;

    $mail->Username = restaurantEnv('RESTAURANT_SMTP_USER');

    $mail->Password = restaurantEnv('RESTAURANT_SMTP_PASSWORD');

    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;

    $mail->Port = 587;


    $mail->setFrom(
        $mail->Username,
        '180 Food House' //nome de quem manda
    );

    foreach ($envio as $email) {
        $mail->addAddress($email);
    }

    $mail->isHTML(true);

    $mail->Subject = 'Relatório de pagamento semanal';

    $mail->Body = $html;


    $mail->send();

    echo 'E-mail enviado com sucesso!';
} catch (Exception $e) {

    echo 'Erro ao enviar: ' . $mail->ErrorInfo;
}
