<?php
session_start();

// Verificação de login e tipo de usuário
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
} else if ($_SESSION['tipo'] !== 'admin') {
    echo "<script> alert('Acesso negado.'); window.location.href = '../../index.php'; </script>";
    exit;
}

// Conexão com o banco
$mysqli = include __DIR__ . '/../banco/conectar.php';


$usuario_id = $_SESSION['usuario_id'] ?? null;
$nomee = $_SESSION['nomeconsulta'] ?? null;
// Filtro de datas e nome
if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['editar_linha'])) {
    $_SESSION['datainicio'] = $_POST['datainicio'] ?? '';
    $_SESSION['datafim'] = $_POST['datafim'] ?? '';
    $_SESSION['nomeconsulta'] = $_POST['nomeconsulta'] ?? '';
    header("Location: ../admin/pagamento.php");
    exit;
}

$datainicio = $_SESSION['datainicio'] ?? '';
$datafim = $_SESSION['datafim'] ?? '';

if ($datainicio && $datafim) {
    $ban = $mysqli->prepare("SELECT dat, taxas, saidas, entradas, gasolina, diaria FROM pagmot WHERE nome = ? AND dat BETWEEN ? AND ? ORDER BY dat ASC");
    $ban->bind_param("sss", $nomee, $datainicio, $datafim);
    $ban->execute();
    $res = $ban->get_result();
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Lista de Pagamentos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/pagamento.css">
    <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>

<body class="admin-module-page">
    <?php include __DIR__ . '/navbar_admin.php'; ?>
    <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Menu</h5>
            <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body sidebar">
            <ul class="nav flex-column">
                <li class="nav-item"><a class="nav-link active" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a></li>
               <li class="nav-item"><a class="nav-link" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar d-none d-md-block">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link active" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a></li>
                     <li class="nav-item"><a class="nav-link" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
                </ul>
            </div>

            <!-- CONTEÚDO PRINCIPAL -->
            <div class="col-md-10 content module-content">
                <header class="module-page-header">
                    <div class="module-page-copy">
                        <span class="module-eyebrow"><i class="bi bi-file-earmark-bar-graph"></i> RELATÓRIOS</span>
                        <h1 class="module-page-title">Lista de pagamentos</h1>
                        <p class="module-page-subtitle">Consulte valores, edite lançamentos e acompanhe os saldos de cada motoboy por período.</p>
                    </div>
                    <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-receipt-cutoff"></i></span>
                </header>

                <div class="card p-4 mb-4">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="datainicio" class="form-label">Data início:</label>
                                <input type="date" class="form-control" id="datainicio" name="datainicio" required>
                            </div>
                            <div class="col-md-4">
                                <label for="datafim" class="form-label">Data fim:</label>
                                <input type="date" class="form-control" id="datafim" name="datafim">
                            </div>
                            <div class="col-md-4">
                                <label for="nomeconsulta" class="form-label">Motoboy:</label>
                                <input type="text" class="form-control" id="nomeconsulta" name="nomeconsulta">
                            </div>
                        </div>
                        <button type="submit" id="botao" class="btn btn-submit w-100 disabled" disabled><i class="bi bi-search"></i> Buscar</button>
                    </form>
                </div>

                <div class="card p-4">
                    <?php
                    $total_taxas = 0;
                    $total_saidas = 0;
                    $total_entradas = 0;
                    $total_final = 0;
                    $total_diaa = 0;
                    $saldobruto = 0;
                    $total_saldodia = 0;
                    ?>

                    <?php if (!$datainicio || !$datafim): ?>
                        <div class="alert alert-info text-center">Insira uma data para realizar a busca.</div>
                    <?php elseif ($res && $res->num_rows > 0): ?>
                        <div class="table-responsive">
                            <div class="mb-3">
                                <button id="editar-todas" class="btn btn-warning">Editar</button>
                                <button id="salvar-todas" class="btn btn-success d-none">Salvar</button>
                            </div>

                            <table class="table table-striped table-hover tabela-edicao sem-edicao">
                                <thead>
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
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Contagem gasolina não paga
                                    $cont = $mysqli->prepare("SELECT COUNT(0) FROM pagmot WHERE nome = ? AND gasolina = 'nao pago' AND dat BETWEEN ? AND ?");
                                    $cont->bind_param("sss", $nomee, $datainicio, $datafim);
                                    $cont->execute();
                                    $cont->bind_result($count);
                                    $cont->fetch();
                                    $cot = (int)$count;
                                    $cont->close();

                                    // Soma diária
                                    $dia = $mysqli->prepare("SELECT SUM(taxas) AS total_taxas FROM pagmot WHERE nome = ? AND diaria = 'pago' AND dat BETWEEN ? AND ?");
                                    $dia->bind_param("sss", $nomee, $datainicio, $datafim);
                                    $dia->execute();
                                    $result = $dia->get_result();
                                    $total_dia = 0;
                                    if ($row = $result->fetch_assoc()) {
                                        $total_dia = $row['total_taxas'] ?? 0;
                                    }
                                    $dia->close();

                                    $total_gasolina = 0;

                                    $gasolina = $mysqli->prepare("SELECT valor_gasolina FROM rules ORDER BY date_last_update DESC LIMIT 1; ");
                                    $gasolina->execute();
                                    $result_gasolina = $gasolina->get_result();
                                    $valor_gasolina = $result_gasolina->fetch_assoc()['valor_gasolina'] ?? 0;
                                    $gasolina->close();


                                    while ($row = $res->fetch_assoc()):
                                        $gasolina = trim(strtolower($row['gasolina']));
                                        $diaria = trim(strtolower($row['diaria']));


                                        $gasolina_saldo = ($row['gasolina'] === 'nao pago' && $row['diaria'] !== 'pago') ? $valor_gasolina : 0;

                                        $gasolina_saldofake = ($row['gasolina'] === 'nao pago') ? $valor_gasolina : 0;
                                        $total_saldodiafake = $row['taxas'] + $row['entradas'] + $gasolina_saldofake - $row['saidas'];


                                        $total_diaa = $row['taxas'] + $row['entradas']  + $valor_gasolina;
                                        $total_saldodia = $row['taxas'] + $row['entradas']  - $row['saidas'] + $gasolina_saldo;

                                        // Saldo do dia (só conta se a diária não foi paga)
                                        if ($row['diaria'] === 'pago') {

                                            $total_saldodiaa = 0; // já quitado, não entra no saldo
                                        } else {
                                            $total_saldodiaa = $total_saldodia;
                                        }



                                    ?>
                                        <tr class="linha-edicao" data-data="<?= $row['dat'] ?>" data-motoboy="<?= $nomee ?>">

                                            <form method='POST' action="../admin/pagamento_api.php" class="meuFormulario">
                                                <input type='hidden' name='editar_linha' value='1'>
                                                <input type='hidden' name='data_linha' value='<?= $row['dat'] ?>'>
                                                <input type='hidden' name='motoboy' value='<?= $nomee ?>'>



                                                <td>


                                                    <input type='hidden' name='data_linha' value='<?= $row['dat'] ?>'>
                                                    <input type='hidden' name='motoboy' value='<?= $nomee ?>'>

                                                    <?= $row['dat'] === '00-00-0000' ? 'N/A' : date('d/m/Y', strtotime($row['dat']))  ?>
                                                </td>
                                                <td><span class="valor-visivel"><?= $nomee ?></span></td>
                                                <td>
                                                    <span class="valor-visivel"><?= number_format($row['taxas'], 2, ',', '.') ?></span>
                                                    <input type='number' class='form-control form-editar d-none' name='taxas' value='<?= $row['taxas'] ?>' step='0.01'>
                                                </td>
                                                <td>
                                                    <span class="valor-visivel"><?= number_format($row['saidas'], 2, ',', '.') ?></span>
                                                    <input type='number' class='form-control form-editar d-none' name='saidas' value='<?= $row['saidas'] ?>' step='0.01'>
                                                </td>
                                                <td>
                                                    <span class="valor-visivel"><?= number_format($row['entradas'], 2, ',', '.') ?></span>
                                                    <input type='number' class='form-control form-editar d-none' name='entradas' value='<?= $row['entradas'] ?>' step='0.01'>
                                                </td>
                                                <td>
                                                    <span class="valor-visivel"><?= htmlspecialchars($row['gasolina']) ?></span>
                                                    <select name='gasolina' class='form-select form-editar d-none'>
                                                        <option value='pago' <?= $row['gasolina'] == 'pago' ? 'selected' : '' ?>>Pago</option>
                                                        <option value='nao pago' <?= $row['gasolina'] == 'nao pago' ? 'selected' : '' ?>>Não pago</option>
                                                    </select>
                                                </td>
                                                <td><span class="valor-visivel"><?= number_format($total_diaa, 2, ',', '.') ?></span></td>
                                                <td><span class="valor-visivel"><?= number_format($total_saldodiafake, 2, ',', '.') ?></span></td>
                                                <td>
                                                    <span class="valor-visivel"><?= htmlspecialchars($row['diaria']) ?></span>
                                                    <select name='diaria' class='form-select form-editar d-none'>
                                                        <option value='pago' <?= $row['diaria'] == 'pago' ? 'selected' : '' ?>>Pago</option>
                                                        <option value='nao pago' <?= $row['diaria'] == 'nao pago' ? 'selected' : '' ?>>Não pago</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm btn-excluir">❌</button>
                                                </td>
                                            </form>
                                        </tr>
                                    <?php

                                        // Acumuladores corretos
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
                                        <td>R$<?= number_format($total_final, 2, ',', '.') ?></td>
                                        <td>R$<?= number_format($Saldo_final, 2, ',', '.') ?></td>
                                        <td></td>
                                        <td></td>
                                </tbody>



                            </table>
                            <span>O valor liquido é calculado como: taxas + entradas + gasolina (se marcada como não paga) - saídas - diária (se marcada como paga).</span>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">Nenhum registro encontrado no período selecionado.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>

    <script src="../../assets/js/admin/relatorios/filtro-periodo.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="../../assets/js/admin/relatorios/salvar-edicao.js"></script>
    <script src="../../assets/js/admin/relatorios/edicao-em-lote.js"></script>


  <script src="../../assets/js/admin/relatorios/excluir-pagamento.js"></script>


    <?php
    // Limpar sessão
    unset($_SESSION['datainicio'], $_SESSION['datafim'], $_SESSION['nomeconsulta']);
    ?>
</body>

</html>
