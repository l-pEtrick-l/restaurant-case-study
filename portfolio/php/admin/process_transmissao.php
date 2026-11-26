<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/ambiente.php';
  $input = json_decode(file_get_contents('php://input'), true);

// Integração opcional: configuração obrigatória para enviar mensagens.
$instance = restaurantEnv('RESTAURANT_WHATSAPP_INSTANCE');
$baseUrl = restaurantEnv('RESTAURANT_WHATSAPP_URL');
$apikey = restaurantEnv('RESTAURANT_WHATSAPP_KEY');
if ($instance === '' || $baseUrl === '' || $apikey === '') {
    http_response_code(503);
    echo json_encode(['ok' => false, 'message' => 'Integração de mensagens não configurada.']);
    exit;
}

$mysqli = include __DIR__ . '/../banco/conectar.php';

   if (!$input || empty($input['campanha']) || empty($input['mensagem'])) {
        throw new Exception("Dados inválidos enviados ao servidor (precisa campanha e mensagem).");
    }

$campanha = trim($input['campanha']);

$stmt = $mysqli->prepare("SELECT SUM(quantidade_active_clients + quantidade_inactive_clients + quantidade_potential_clients)
FROM clients
WHERE campanha = ?");
$stmt->bind_param("s", $campanha);
$stmt->execute();
$totalClientsRow = $stmt->get_result()->fetch_row();
$stmt->close();

$maxBatch = $totalClientsRow ? intval($totalClientsRow[0]) : 0;

// Verifica método HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'Use POST']);
    exit;
}


 try {
    // Lê o JSON de entrada

    $mensagem = $input['mensagem'];
    $imagem = $input['imagem'] ?? null;

    // Busca a campanha
    $stmt = $mysqli->prepare("SELECT * FROM clients WHERE campanha = ?");
    $stmt->bind_param("s", $campanha);
    $stmt->execute();
    $campanhaRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();


    if (!$campanhaRow) throw new Exception("Campanha não encontrada no banco de dados.");


    // Determina tabelas a serem usadas

    // Monta SQL para pegar clientes de todas as tabelas
    $sql = [];
    if ($campanhaRow['quantidade_active_clients'] > 0)
        $sql[] = "SELECT nome_cliente, numero_whatsapp FROM excel_client_active WHERE numero_whatsapp IS NOT NULL AND numero_whatsapp != ''";
    if ($campanhaRow['quantidade_inactive_clients'] > 0)
        $sql[] = "SELECT nome_cliente, numero_whatsapp FROM excel_client_inactive WHERE numero_whatsapp IS NOT NULL AND numero_whatsapp != ''";
    if ($campanhaRow['quantidade_potential_clients'] > 0)
        $sql[] = "SELECT nome_cliente, numero_whatsapp FROM excel_client_potential WHERE numero_whatsapp IS NOT NULL AND numero_whatsapp != ''";

    $clientes = [];
    if (!empty($sql)) {
        $query = implode(" UNION ALL ", $sql) . " LIMIT " . intval($maxBatch);

        error_log("DEBUG SQL: $query");

        $res = $mysqli->query($query);
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $clientes[] = $row;
            }
        } else {
            throw new Exception("Erro na query: " . $mysqli->error);
        }
    }

    if (empty($clientes)) throw new Exception("Nenhum cliente encontrado para envio.");


    // Função para enviar mensagem igual ao teste.php
    function sendMessage($baseUrl, $instance, $numero, $texto, $apikey)
    {
        $payload = json_encode([
            "number" => preg_replace('/\D/', '', $numero),
            "text"   => $texto
        ]);

        $ch = curl_init("$baseUrl/message/sendText/$instance");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'apikey: ' . $apikey
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);


        if ($err) {
            return ['ok' => false, 'message' => "Erro cURL: $err"];
        }


        $json = json_decode($response, true);
        if ($json === null) {
            return ['ok' => false, 'message' => "Resposta não JSON: $response"];
        }

        return ['ok' => ($httpCode >= 200 && $httpCode < 300), 'response' => $json];
    }

    // Função de delay (opcional)
    function randomDelay()
    {
        $s = 300;
        //$s = rand(2, 5);
        sleep($s);
    }

    // Envio em lote com logs
    $enviadas = 0;
    $falhas = 0;
    $resultados = [];


    foreach ($clientes as $c) {
        $textoPersonalizado = str_replace("{nome}", $c['nome_cliente'], $mensagem);
        $numero = $c['numero_whatsapp'];

        $res = sendMessage($baseUrl, $instance, $numero, $textoPersonalizado, $apikey);

        $resultados[] = [
            'numero' => $numero,
            'resultado' => $res
        ];

        if ($res['ok']) $enviadas++;
        else $falhas++;

       randomDelay();
    }


   echo json_encode(['ok' => true, 'message' => 'Transmissão iniciada com sucesso!']);
exit;

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
