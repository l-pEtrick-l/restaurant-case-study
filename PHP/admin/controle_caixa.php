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

// Conexão com o banco
$mysqli = include __DIR__ . '/../banco/conectar.php';
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Controle de Caixa</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../CSS/admin/controle.caixa.css">
  <style>

  </style>
</head>

<body>
  <!-- NAVBAR SUPERIOR -->
  <nav class="navbar navbar-expand-lg bg-body-tertiary">
    <div class="container-fluid">
      <a class="navbar-brand" href="../admin/inicial.php">
        <img src="../../img/logofood.png" alt="Logo" class="d-inline-block align-text-top">
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
          <li class="nav-item"><a class="nav-link cor" href="../admin/inicial.php"><i class="bi bi-house-door"></i> Início</a></li>
          <li class="nav-item"><a class="nav-link cor ativo" href="../admin/pagamento.php"><i class="bi bi-file-earmark-excel-fill"></i> Relatórios</a></li>
          <li class="nav-item"><a class="nav-link cor" href="../admin/txs.php"><i class="bi bi-graph-up"></i> Logistico</a></li>

          <li class="nav-item"><a class="nav-link cor" href="../admin/cadastros.php"><i class="bi bi-person"></i> Usuário</a></li>
        </ul>
        <form class="d-flex" method="POST" action="../admin/sair.php">
          <button class="btn sairr btn-danger" name="sair" type="submit">Sair</button>
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
        <li class="nav-item">
          <a class="nav-link" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a>
        </li>


        <li class="nav-item"><a class="nav-link" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
        <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
        <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
        <li class="nav-item"><a class="nav-link active" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
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
          <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>

          <li class="nav-item"><a class="nav-link active" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
        </ul>
      </div>

      <div class="col-md-10 content">
        <h2 class="mb-4"><i class="bi bi-file-earmark-text"></i> Controle de caixa </h2>

        <div class="card p-4 mb-4">
          <form method="POST" action="../admin/process_temporary.php" id="formCaixa">
            <div class="row mb-3">

              <div class="col-md-3">
                <label for="tipo" class="form-label">Tipo</label>
                <select class="form-select" id="tipo" name="tipo" required>
                  <option value="" selected disabled>Selecione...</option>
                  <option value="venda">Venda</option>
                  <option value="saida">Saida comum</option>
                  <option value="gasolina">Gasolina</option>
                  <option value="entrada">Entrada</option>
                </select>
              </div>

              <div class="col-md-3">
                <label class="form-label" for="valor">Valor</label>
                <input type="number" class="form-control" id="valor" name="valor" required>
              </div>

              <div class="col-md-3">
                <label for="nome" class="form-label">Nome</label>
                <select class="form-select" id="nome" name="nome" required>
                  <option value="" selected disabled>Selecione...</option>
                  <?php
                  $nome = $mysqli->query("SELECT nome FROM motoboy");
                  while ($row = $nome->fetch_assoc()) {
                    echo "<option value='" . $row['nome'] . "'>" . $row['nome'] . "</option>";
                  }
                  ?>
                </select>
              </div>
              <div class="col-md-3 d-flex align-items-end">
                <button type="submit" id="botao" class="btn btn-submit w-100 disabled" disabled><i class="bi bi-caret-up-square"></i> Lançar</button>
              </div>
            </div>

          </form>
        </div>

        <!---------------------------------------------------------------------------------------------------------------------------------------------------------------->
        <div class="card p-4 mb-4">
          <div class="row mb-3">
            <table class="table table-striped table-hover tabela-edicao sem-edicao">
              <thead>
                <tr>
                  <th>Nome</th>
                  <th>Tipo</th>
                  <th>Valor</th>
                  <th>Data</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $stmt = $mysqli->prepare("SELECT nome,tipo,valor,data_caixa FROM temporary ORDER BY id_temp DESC");
                $stmt->execute();
                $res = $stmt->get_result();
                while ($row = $res->fetch_assoc()):
                ?>
                  <tr>
                    <td>
                      <span><?= $row['nome'] ?></span>
                    </td>

                    <td>
                      <span> <?= $row['tipo'] ?></span>
                    </td>

                    <td>
                      <span>R$<?= number_format($row['valor'], 2, ",", ".")  ?></span>
                    </td>

                    <td>
                      <span><?= $row['data_caixa'] == '0000-00-00' ? 'N/A' : date('d/m/y', strtotime($row['data_caixa'])) ?></span>
                    </td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
      
                <button type="submit" id="btn-smt" class="btn btn-submit w-100" ><i class="bi bi-caret-up-square"></i> Enviar</button>
         
          </div>
        </div>

      </div>




    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="../../JS/controle_caixa.js"></script>
  <script src="../../JS/sweet_alert_question.js"></script>