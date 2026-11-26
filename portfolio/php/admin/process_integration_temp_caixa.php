<?php
session_start();

$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');

error_log('1 blz');
$caixa = $mysqli->prepare("SELECT id_temp,tipo,valor,nome,descricao,data_caixa FROM temporary");
$caixa->execute();
$result = $caixa->get_result();
$array = $result->fetch_all(MYSQLI_ASSOC);
error_log('2 blz');
$count = count($array);
$loja = "180 Food House";

if ($count == 0) {
    echo json_encode([
        'ok' => false,
        'message' => 'Nenhum lançamento encontrado'
    ]);
    exit;
}
error_log('3 blz');
for($i =0; $i < $count; $i++){
    if($array[$i]['tipo'] == 'gasolina' || $array[$i]['tipo'] == 'saida'){
        $sangria_ou_entrada = 'sangria';
    }else if($array[$i]['tipo'] == 'venda' || $array[$i]['tipo'] == 'entrada'){
        $sangria_ou_entrada = 'suprimento';
        }else {
            $sangria_ou_entrada = 'outro';
        }


    $save = $mysqli->prepare("INSERT INTO excel_caixa (codigo,loja,tipo,descricao,observacao,data_caixa,valor) VALUES (?,?,?,?,?,?,?)");
    $save->bind_param("isssssd", $array[$i]['id_temp'], $loja, $sangria_ou_entrada,$sangria_ou_entrada,
    $array[$i]['descricao'], $array[$i]['data_caixa'], $array[$i]['valor']);
    $save->execute();
    $save->close();
}
error_log('4 blz');
 $tira_temp = $mysqli->prepare("DELETE FROM temporary");
 $tira_temp->execute();
 $tira_temp->close();
error_log('5 blz');
  echo json_encode(['ok' => true, 'message' => 'Caixa lançado com sucesso']);
