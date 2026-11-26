<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
  header("Location: ../../index.php");
  exit;
} else if ($_SESSION['tipo'] !== 'admin') {
  echo "<script>
        alert('Acesso negado.');
        window.location.href = '../../index.php';
    </script>";
  exit;
}

$mysqli = include __DIR__ . '/../banco/conectar.php';


$total_dodia = 0.0;
$NDP_del = 0;
$NDP_IFOOD = 0;
$Total_IFOOD = 0.0;
$total_salao = 0.0;
$fechamento_id = 0;
$mesas = 0;
$caixa_final = 0.0;

$FatDel = 0.0;
$QuantPedAnota = 0;
$TKdelivery = 0.0;
$TKsalao = 0.0;
$FatDelLiqMotEifood = 0.0;
$FatDelLiqMotoboy = 0.0;
$fatfinal = 0.0;

$entregadores = []; // garante que foreach não quebre
$txsmotoboy = 0.0;


$data_inicio = $_GET['datainicial'] ?? null;
$data_fim = $_GET['datafim'] ?? null;


// Se veio via POST, redireciona para GET
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $data_inicio = $_POST['datainicial'] ?? null;
  $data_fim = $_POST['datafim'] ?? null;

  if ($data_inicio && $data_fim) {
    $data_inicio = date('Y-m-d', strtotime($data_inicio));
    $data_fim = date('Y-m-d', strtotime($data_fim));

    // Redireciona com GET (PRG)
    header("Location: ?datainicial=$data_inicio&datafim=$data_fim");
    exit;
  }
  $mesas = $_POST['mesas'] ?? null;
  $caixa_final = $_POST['caixa_final'] ?? null;
  $data_fechamento = $_POST['data_fechamento'] ?? null;
}

// Só executa consulta se tiver datas
if ($data_inicio && $data_fim) {
  $stm = $mysqli->prepare("SELECT SUM(VT) as total FROM excel_data WHERE data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $total_dodia = $row['total'] ?? 0;
  $stm->close();

  $stm = $mysqli->prepare("SELECT COUNT(NDP) AS ndp_del FROM excel_data WHERE origem <> 'Garçom' AND data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $NDP_del = $row['ndp_del'] ?? 0;
  $stm->close();

  $stm = $mysqli->prepare("SELECT COUNT(NDP) AS ndp_ifood FROM excel_data WHERE origem = 'iFood' AND data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $NDP_IFOOD = $row['ndp_ifood'] ?? 0;
  $stm->close();

  $stm = $mysqli->prepare("SELECT SUM(VT) AS total_ifood FROM excel_data WHERE origem = 'iFood' AND data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $Total_IFOOD = $row['total_ifood'] ?? 0;
  $stm->close();

  $stm = $mysqli->prepare("SELECT SUM(VT) AS total_salao FROM excel_data WHERE origem = 'Garçom' AND data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $total_salao = $row['total_salao'] ?? 0;
  $stm->close();

  $stm = $mysqli->prepare("
      SELECT SUM(m.mesas) AS total_mesas
      FROM `manual` m
      WHERE m.fechamento_id IN (
          SELECT DISTINCT fechamento_id
          FROM excel_data
          WHERE data_fechamento BETWEEN ? AND ? AND `status` <> 'Cancelado'
      )
");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();

  $mesas = 0;
  if ($row = $result->fetch_assoc()) {
    $mesas = (int)$row['total_mesas'];
  }
  $stm->close();




  $stm = $mysqli->prepare("
      SELECT SUM(m.caixa_final) AS caixa_final
      FROM `manual` m
      WHERE m.fechamento_id IN (
          SELECT DISTINCT fechamento_id
          FROM excel_data
          WHERE data_fechamento BETWEEN ? AND ?
      )
");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();

  $caixa_final = 0;
  if ($row = $result->fetch_assoc()) {
    $caixa_final = (int)$row['caixa_final'];
  }
  $stm->close();





  $stm = $mysqli->prepare("SELECT entregador AS entregador FROM excel_data WHERE data_fechamento BETWEEN ? AND ?");
  $stm->bind_param("ss", $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $entregador = $row['entregador'] ?? 0;
  $stm->close();

$stmt = $mysqli->prepare("
    SELECT taxa_ifood_zerada, valor_gasolina
    FROM rules
    ORDER BY rules_id DESC
    LIMIT 1
");
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

$taxa_ifood_zerada = $row['taxa_ifood_zerada'] ?? 0;
$valor_gasolina    = $row['valor_gasolina'] ?? 0;

$stmt->close();


  $stm = $mysqli->prepare("SELECT entregador, SUM(CASE WHEN vf = 0 THEN ? ELSE vf END) + ? AS total_frete FROM excel_data WHERE data_fechamento BETWEEN ? AND ? AND entregador IS NOT NULL AND entregador <> '' GROUP BY entregador;");
  $stm->bind_param("ddss", $taxa_ifood_zerada, $valor_gasolina, $data_inicio, $data_fim);
  $stm->execute();
  $result = $stm->get_result();
  $entregadores = [];
  while ($row = $result->fetch_assoc()) {
    $entregadores[] = $row;
  }
  $stm->close();

  $txsmotoboy = 0.0;
  if (!empty($entregadores)) {
    $txsmotoboy = array_sum(array_column($entregadores, 'total_frete'));
  }

  $stm = $mysqli->prepare("SELECT valor_gasolina FROM rules");
  $stm->execute();
  $result = $stm->get_result();
  $row = $result->fetch_assoc();
  $valor_minimo = $row['valor_gasolina'];
  $stm->close();


  // --- cálculos finais com checagem para evitar divisão por zero ---
  $FatDel = $total_dodia - $total_salao;
  $QuantPedAnota = $NDP_del - $NDP_IFOOD;
  $TKdelivery = $NDP_del ? ($FatDel / $NDP_del) : 0.0;
  $TKsalao = $mesas ? ($total_salao / $mesas) : 0.0;
  $FatDelLiqMotEifood = $FatDel - $Total_IFOOD - $txsmotoboy;
  $quantidade_motoboys = count($entregadores);
  if(($quantidade_motoboys * $valor_minimo) <= ($txsmotoboy)){
    $min_atingido = $txsmotoboy;
  }else{
    $min_atingido = ($quantidade_motoboys * $valor_minimo);
  }

  $FatDelLiqMotoboy = $FatDel - $min_atingido;
  $fatfinal = $FatDelLiqMotEifood + $total_salao;
}


?>






<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Fechamento Diario</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/admin/fechamentodiario.css">
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
        <li class="nav-item">
          <a class="nav-link" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a>
        </li>
        <li class="nav-item"><a class="nav-link" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
        <li class="nav-item"><a class="nav-link active" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
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
          <li class="nav-item">
            <a class="nav-link" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a>
          </li>

           <li class="nav-item"><a class="nav-link" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
          <li class="nav-item"><a class="nav-link active" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
        </ul>
      </div>


      <div class="col-md-10 content module-content">
        <header class="module-page-header">
          <div class="module-page-copy">
            <span class="module-eyebrow"><i class="bi bi-calendar2-check"></i> RELATÓRIOS</span>
            <h1 class="module-page-title">Fechamento diário</h1>
            <p class="module-page-subtitle">Consolide delivery, salão, ticket médio e resultados financeiros em uma visão organizada.</p>
          </div>
          <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-graph-up-arrow"></i></span>
        </header>

        <div class="card p-4">
          <h4 class="mb-4"><i class="bi bi-calendar-check"></i> Consultar Dia</h4>
          <form method="POST" action="" enctype="multipart/form-data" class="meuFormulario">
            <div class="row mb-3">
              <div class="col-md-4">
                <label for="datainicial" class="form-label">Data inicial</label>
                <input name="datainicial" id="datainicial" class="form-control" type="date">
              </div>

              <div class="col-md-4">
                <label for="datafim" class="form-label">Data final</label>
                <input name="datafim" id="datafim" class="form-control" type="date">
              </div>

              <div class="col-md-4 d-flex align-items-end">
                <button class="btn btn-warning" type="submit">Buscar</button>
              </div>
            </div>
          </form>
          <div class="text-end mb-3">
            <button id="btnPrintZap" class="btn btn-success d-none">
              <i class="bi bi-card-image"></i> Baixar fechamento diario
            </button>
          </div>

          <?php if (!$data_inicio || !$data_fim): ?>
            <div class="alert alert-warning mt-4" role="alert">
              <i class="bi bi-exclamation-triangle"></i> Por favor, selecione uma data inicial e uma data final para visualizar o fechamento.
            </div>
          <?php elseif ($total_dodia == 0): ?>
            <div class="alert alert-info mt-4" role="alert">
              <i class="bi bi-info-circle"></i> Nenhum resultado encontrado para o período selecionado.
            </div>
          <?php else: ?>

            <div class="card fundotab p-4 modern-dashboard">
              <div class="card-header exibirdatafe d-flex justify-content-between align-items-center">
                <spam>Data do fechamento: <?php
                                          $data_fechamentoini = null;
                                          if (!empty($data_inicio)) {
                                            $dateObj = DateTime::createFromFormat('Y-m-d', $data_inicio);
                                            $data_fechamentoini = $dateObj ? $dateObj->format('d/m/Y') : null;
                                          }
                                          $data_fechamentofim = null;
                                          if (!empty($data_fim)) {
                                            $dateObj = DateTime::createFromFormat('Y-m-d', $data_fim);
                                            $data_fechamentofim = $dateObj ? $dateObj->format('d/m/Y') : null;
                                          }
                                          echo $data_fechamentoini ?> até <?= $data_fechamentofim ?></spam>
              </div>

              <div class="row mt-4 g-4">
                <!-- CARD DELIVERY -->
                <div class="col-md-4">
                  <div class="summary-card">
                    <div class="summary-header">
                      <i class="bi bi-bag-check"></i> Delivery
                    </div>
                    <div class="summary-body">
                      <p><strong>Pedidos:</strong> <?= $NDP_del ?></p>
                      <p><strong>Anota.Ai:</strong> <?= $QuantPedAnota ?></p>
                      <p><strong>iFood:</strong> <?= $NDP_IFOOD ?></p>
                      <p><strong>Faturamento Total:</strong> R$ <?= number_format($FatDel, 2, ',', '.') ?></p>
                      <p><strong>Faturamento iFood:</strong> R$ <?= number_format($Total_IFOOD, 2, ',', '.') ?></p>
                      <p><strong>Fat Líquido Motoboy:</strong> R$ <?= number_format($FatDelLiqMotoboy, 2, ',', '.') ?></p>
                      <p><strong>TK Delivery: R$ <?= number_format($TKdelivery, 2, ',', '.') ?></p>
                      <div class="highlight">Fat Líquido Mot/iFood:</strong> R$ <?= number_format($FatDelLiqMotEifood, 2, ',', '.') ?></div>
                    </div>
                  </div>
                </div>

                <!-- CARD SALÃO -->
                <div class="col-md-4">
                  <div class="summary-card">
                    <div class="summary-header">
                      <i class="bi bi-cup-hot"></i> Salão
                    </div>
                    <div class="summary-body">
                      <p><strong>Pedidos:</strong> <?= $mesas ?></p>
                      <p><strong>Faturamento:</strong> R$ <?= number_format($total_salao, 2, ',', '.') ?></p>
                      <div class="highlight">TK Salão: R$ <?= number_format($TKsalao, 2, ',', '.') ?></div>
                    </div>
                  </div>

                  <div class="summary-card mt-4">
                    <div class="summary-header">
                      <i class="bi bi-cash-stack"></i> Faturamento / Caixa
                    </div>
                    <div class="summary-body">
                      <p><strong>Caixa Final: R$ <?= number_format($caixa_final, 2, ',', '.') ?></p>
                      <div class="highlight">Faturamento Final:</strong> R$ <?= number_format($fatfinal, 2, ',', '.') ?></div>
                    </div>
                  </div>
                </div>

                <!-- CARD MOTOBOY -->
                <div class="col-md-4">
                  <div class="summary-card">
                    <div class="summary-header">
                      <i class="bi bi-bicycle"></i> Fechamento Motoboy
                    </div>
                    <div class="summary-body">
                      <?php if (empty($entregadores)): ?>
                        <p class="text-muted text-center">Nenhum motoboy encontrado</p>
                      <?php else: ?>
                        <?php foreach ($entregadores as $ent): ?>
                          <div class="d-flex justify-content-between align-items-center motoboy-item">
                            <span><?= htmlspecialchars($ent['entregador']) ?></span>
                            <span class="valor">R$ <?= number_format($ent['total_frete'] < 70 ? 70 : $ent['total_frete'], 2, ',', '.') ?></span>
                          </div>
                        <?php endforeach; ?>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>

          <?php endif; ?>

          <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
          <script>
const temFechamento = <?= json_encode(($data_inicio && $data_fim && $total_dodia > 0)) ?>;
</script>
          <script src="../../assets/js/admin/fechamento-diario.js"></script>





        </div>
      </div>
    </div>

</body>

</html>
