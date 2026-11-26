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

$mysqli = include __DIR__ . '/../banco/conectar.php';

$usuario_id = $_SESSION['usuario_id'] ?? null;
$nome = $_SESSION['nome'] ?? null;
if (!$usuario_id) {
    echo "<script>alert('Usuário não identificado.');</script>";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $_SESSION['datainicio'] = $_POST['datainicio'] ?? '';
    $_SESSION['datafim'] = $_POST['datafim'] ?? '';
    header("Location: ../user/minhastaxas.php");
    exit;
}
$datainicio = $_SESSION['datainicio'] ?? '';
$datafim = $_SESSION['datafim'] ?? '';

if($datainicio && $datafim) {
$ban = $mysqli->prepare("SELECT dat, taxas, saidas, entradas, gasolina, diaria FROM pagmot WHERE nome = ? AND dat BETWEEN ? AND ?");
$ban->bind_param("sss", $nome, $datainicio, $datafim);
$ban->execute();
$res = $ban->get_result();
}
unset($_SESSION['datainicio'], $_SESSION['datafim']);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>180 Foodhouse - Minhas Taxas</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
<link rel="icon" type="image/png" href="../../img/logofood.png">
<link rel="stylesheet" href="../../assets/css/user/minhastaxas.css">
</head>
<body>

<nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
        <a class="navbar-brand" href="../user/inicial.php">
            <img src="../../img/logofood.png" alt="Logo">
        </a>
          <button class="btn btn-warning d-lg-none ms-2 aa" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral">
  <i class="bi bi-list-nested"></i> Opções
</button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item A"><a class="nav-link cor" href="../user/inicial.php"><i class="bi bi-house-door"></i> Início</a></li>
                <li class="nav-item A B"><a class="nav-link ativo cor" href="../user/minhastaxas.php"><i class="bi bi-wallet2"></i> Minhas Taxas</a></li>
            </ul>
            <form class="d-flex" method="POST" action="../user/sair.php">
                <button class="btn btn-danger" name="sair" type="submit">Sair</button>
            </form>
        </div>
    </div>
</nav>


<div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body sidebar">
      <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="../user/minhastaxas.php"><i class="bi bi-wallet2"></i> Minhas Taxas</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-graph-up"></i> Análises</a></li>
            </ul>
  </div>
</div>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-2 sidebar d-none d-md-block">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="../user/minhastaxas.php"><i class="bi bi-wallet2"></i> Minhas Taxas</a></li>
                <li class="nav-item"><a class="nav-link" href="#"><i class="bi bi-graph-up"></i> Análises</a></li>
            </ul>
        </div>

        <!-- Conteúdo -->
        <div class="col-md-10 content">
            <h2 class="mb-4"><i class="bi bi-wallet2"></i> Minhas Taxas</h2>

            <div class="card p-4">
                <form method="POST" action="">
                    <div class="row g-3">
                        <div class="col-md-5">
                            <label for="datainicio" class="form-label">Data início</label>
                            <input type="date" class="form-control" id="datainicio" name="datainicio" required>
                        </div>
                        <div class="col-md-5">
                            <label for="datafim" class="form-label">Data fim</label>
                            <input type="date" class="form-control" id="datafim" name="datafim">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" id="botao" class="btn btn-submit w-100">Buscar</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <?php
              $total_taxas = 0;
                $total_saidas = 0;
                $valor_gasolina = 30;
                $total_entradas = 0;
                $total_final = 0;
                $total_diaa = 0;
                $saldobruto = 0;
                $total_saldodia = 0;
                ?>

                <?php if (!$datainicio || !$datafim): ?>
                    <div class="alert alert-info text-center mt-4">Insira uma data para realizar a busca.</div>
                <?php elseif ($res && $res->num_rows > 0): ?>
                        <div style="max-height: 350px; overflow-y: auto;">

                    <table class="table table-striped table-hover mt-3">
                        <thead class="table-dark">
                            <tr>
                                        <th>Dia</th>
                                        <th>Nome</th>
                                        <th>Taxas</th>
                                        <th>Saídas</th>
                                        <th>caixinhas/entradas</th>
                                        <th>Valor Gasolina pago?</th>
                                        <th>Total Bruto</th>
                                        <th>Total Liquido</th>
                                        <th>Status</th>

                            </tr>
                        </thead>

                        <tbody>
                        <?php while($row = $res->fetch_assoc()):
                        $valor_gasolina = 30;
     $gasolina = trim(strtolower($row['gasolina']));
                                    $diaria = trim(strtolower($row['diaria']));


$gasolina_saldo = ($row['gasolina'] === 'nao pago' && $row['diaria'] !== 'pago') ? $valor_gasolina : 0;

$gasolina_saldofake = ($row['gasolina'] === 'nao pago') ? $valor_gasolina : 0;
$total_saldodiafake = $row['taxas'] + $row['entradas'] + $gasolina_saldofake - $row['saidas'];


$total_diaa = $row['taxas'] + $row['entradas']  + $valor_gasolina;
$total_saldodia = $row['taxas'] + $row['entradas']  - $row['saidas'] + $gasolina_saldo;

if ($row['diaria'] === 'pago') {
    $total_saldodiaa = 0;// já quitado, não entra no saldo
} else {
    $total_saldodiaa = $total_saldodia;
}

                         ?>

                            <tr>
                                <td>
                               <?= $row['dat'] === '00-00-0000' ? 'N/A' : date('d/m/Y', strtotime($row['dat']))  ?>
                                </td>
                                <td><span class="valor-visivel"><?= $nome ?></span></td>
                                <td>R$ <?= number_format($row['taxas'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($row['saidas'], 2, ',', '.') ?></td>
                                <td>R$ <?= number_format($row['entradas'], 2, ',', '.') ?></td>
                                <td><?= strtolower(trim($row['gasolina'])) === 'pago' ? "Pago" : "Não pago" ?></td>
                                    <td><span class="valor-visivel"><?= number_format($total_diaa, 2, ',', '.') ?></span></td>
                                <td><span class="valor-visivel"><?= number_format($total_saldodiafake, 2, ',', '.') ?></span></td>
                                <td><?= strtolower(trim($row['diaria'])) === 'pago' ? "Pago" : "Não pago" ?></td>
                            </tr>
                            <?php

                            $cont = $mysqli->prepare("SELECT COUNT(0) FROM pagmot WHERE nome = ? AND gasolina = 'nao pago' AND dat BETWEEN ? AND ?");
                            $cont->bind_param("sss", $nome, $datainicio, $datafim);
                            $cont->execute();
                            $cont->bind_result($count);
                            $cont->fetch();
                            $cot = (int)$count;
                            $cont->close();

                            $dia = $mysqli->prepare("SELECT SUM(taxas) AS total_taxas FROM pagmot WHERE nome = ? AND diaria = 'pago' AND dat BETWEEN ? AND ?");
                            $dia->bind_param("sss", $nome, $datainicio, $datafim);
                            $dia->execute();
                            $result = $dia->get_result();

                            $total_dia = 0;
                            if ($row_dia = $result->fetch_assoc()) {
                                $total_dia = $row_dia['total_taxas'] ?? 0;
                            }


                                     $total_taxas += $row['taxas'];
$total_saidas += $row['saidas'];
$total_entradas += $row['entradas'];
$total_gasolina_saldo += $gasolina_saldo;
$totalfinalsaldodia += $total_saldodiaa;
$totalreal_gasolina_saldo += $valor_gasolina;

$Saldo_final = $totalfinalsaldodia;
$total_final = $total_taxas + $total_entradas + $totalreal_gasolina_saldo;
endwhile;

                            ?>

                   <tr class="table-secondary fw-bold">
                                         <td>Total:</td>
                                    <td></td>
                                    <td>R$ <?= number_format($total_taxas, 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($total_saidas, 2, ',', '.') ?></td>
                                    <td>R$ <?= number_format($total_entradas, 2, ',', '.') ?></td>
                                    <td></td>
                                    <td><?=number_format($total_final, 2, ',', '.') ?></td>
                                    <td><?=number_format($Saldo_final, 2, ',', '.') ?></td>
                                    <td></td>
                                </tr>

                        </tbody>
                    </table>
                    </div>
                    </div>
                    <p>O Saldo final é calculado como: total das taxas + total das entradas + gasolina (se marcada como não paga) - saídas.</p>
                <?php else: ?>
                    <div class="alert alert-warning text-center mt-4">Nenhum registro encontrado no período selecionado.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="../../assets/js/user/minhas-taxas.js"></script>
</body>
</html>
