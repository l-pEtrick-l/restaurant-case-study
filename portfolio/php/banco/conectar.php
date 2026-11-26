<?php

require_once __DIR__ . '/../config/ambiente.php';

try {
    $conexao = new mysqli(
        restaurantEnv('RESTAURANT_DB_HOST', '127.0.0.1'),
        restaurantEnv('RESTAURANT_DB_USER', 'restaurant_case'),
        restaurantEnv('RESTAURANT_DB_PASSWORD'),
        restaurantEnv('RESTAURANT_DB_NAME', 'restaurant_case'),
        (int) restaurantEnv('RESTAURANT_DB_PORT', '3306')
    );
    $conexao->set_charset('utf8mb4');
} catch (mysqli_sql_exception) {
    http_response_code(503);
    exit('Banco de dados indisponível. Verifique a configuração local.');
}

return $conexao;
