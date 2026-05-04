<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
} else if ($_SESSION['tipo'] !== 'motoboy') {
    echo "<script>
    alert('Acesso negado.');
     window.location.href = '../../index.php';
    </script>";
    exit;
}

include '../banco/conectar.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo "<script>alert('Usuário não identificado.');</script>";
    exit;
}

?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>180 Foodhouse - Inicio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link rel="stylesheet" href="../../CSS/inicial.css">
</head>
 
<body>
<nav class="navbar shadow-boxx navbar-expand-lg">
    <div class="container-fluid">
        <a class="navbar-brand" href="../user/inicial.php">
            <img src="../../img/logofood.png" alt="Logo" class="d-inline-block align-text-top">
        </a>
        <button class="navbar-toggler bg-light" type="button" data-bs-toggle="collapse"
            data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
            aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0"></ul>
            <form class="d-flex" method="POST" action="../user/sair.php">
                <button class="btn ss" name="sair" type="submit">Sair</button>
            </form>
        </div>
    </div>
</nav>

<div class="min-vh-100 d-flex justify-content-center align-items-center">
    <div class="div_inicial shadow-box">
        <h1><strong>Bem-vindo, <?= htmlspecialchars($_SESSION['nome']) ?>!</strong></h1>
        <p>Caso tenha dúvida de algo, consulte o atendimento</p>
             <a href="../user/minhastaxas.php" class="zap">Minhas Taxas</a>
         <a href="mailto:www.bryanpetrick@gmail.com?subject=Preciso%20de%20ajuda?body=Olá,%20preciso%20de%20ajuda%20com" class="zap mt-4">Preciso de ajuda</a>
   
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../JS/inicial.js"></script>
</body>
</html>
