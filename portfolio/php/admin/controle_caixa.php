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
  <link rel="stylesheet" href="../../assets/css/admin/controle-caixa.css">
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

      <div class="col-md-10 content module-content">
        <header class="module-page-header">
          <div class="module-page-copy">
            <span class="module-eyebrow"><i class="bi bi-cash-stack"></i> RELATÓRIOS</span>
            <h1 class="module-page-title">Controle de caixa</h1>
            <p class="module-page-subtitle">Registre entradas e saídas, revise os lançamentos temporários e envie o movimento do caixa.</p>
          </div>
          <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-wallet2"></i></span>
        </header>

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
$stmt = $mysqli->prepare("SELECT id_temp, nome, tipo, valor, data_caixa FROM temporary ORDER BY id_temp DESC");
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()):
?>
  <tr data-id="<?= $row['id_temp'] ?>">
    <td><span><?= $row['nome'] ?></span></td>
    <td><span><?= $row['tipo'] ?></span></td>
    <td><span>R$<?= number_format($row['valor'], 2, ",", ".") ?></span></td>
    <td><span><?= $row['data_caixa'] == '0000-00-00' ? 'N/A' : date('d/m/y', strtotime($row['data_caixa'])) ?></span></td>
    <td>
      <button type="button" class="btn btn-danger btn-sm btn-excluir">❌</button>
    </td>
  </tr>
<?php endwhile; ?>
              </tbody>
            </table>

            <button type="submit" id="btn-smt" class="btn btn-submit w-100"><i class="bi bi-caret-up-square"></i> Enviar</button>

          </div>
        </div>

      </div>




    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>





  <script src="../../assets/js/admin/controle-caixa/formulario.js"></script>


  <script src="../../assets/js/admin/controle-caixa/confirmar-lancamento.js"></script>




  <script src="../../assets/js/admin/controle-caixa/excluir-lancamento.js"></script>
