<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../php/config/ambiente.php';

if (restaurantEnv('RESTAURANT_DB_NAME', 'restaurant_case') !== 'restaurant_case'
    || !in_array(restaurantEnv('RESTAURANT_DB_HOST', '127.0.0.1'), ['127.0.0.1', 'localhost'], true)) {
    fwrite(STDERR, "Use apenas o banco local descartável restaurant_case.\n");
    exit(1);
}

$mysqli = require __DIR__ . '/../php/banco/conectar.php';
$existing = $mysqli->query('SHOW TABLES');
if ($existing->num_rows !== 0) {
    fwrite(STDERR, "O banco precisa estar vazio. Nenhuma tabela foi modificada.\n");
    exit(1);
}

$mysqli->multi_query(file_get_contents(__DIR__ . '/demo.sql'));
while ($mysqli->more_results()) {
    $mysqli->next_result();
}

// Gere uma senha nova em cada instalação local. Nenhuma senha de demo é versionada.
$senha = bin2hex(random_bytes(9));
$hash = password_hash($senha, PASSWORD_DEFAULT);
$email = 'admin@example.test';
$stmt = $mysqli->prepare("INSERT INTO usuario (email, senha, tipo) VALUES (?, ?, 'admin')");
$stmt->bind_param('ss', $email, $hash);
$stmt->execute();
$id = $mysqli->insert_id;
$stmt = $mysqli->prepare("INSERT INTO admin (usuario_id, nome) VALUES (?, 'Administração Demo')");
$stmt->bind_param('i', $id);
$stmt->execute();
printf("Demo local criada. E-mail: %s\nSenha: %s\n", $email, $senha);
