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
  <title>Dashboard - Lista de Pagamentos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/admin/uploadarquivos.css">
  <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>
<body class="admin-module-page upload-page">
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
                      <li class="nav-item"><a class="nav-link active" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
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
          <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
          <li class="nav-item"><a class="nav-link active" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
          <li class="nav-item"><a class="nav-link" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
        </ul>
       </div>



    <!-- CONTEÚDO PRINCIPAL -->
    <div class="col-md-10 content module-content">
      <header class="module-page-header">
        <div class="module-page-copy">
          <span class="module-eyebrow"><i class="bi bi-cloud-arrow-up"></i> RELATÓRIOS</span>
          <h1 class="module-page-title">Central de importações</h1>
          <p class="module-page-subtitle">Envie planilhas de pedidos e bases de clientes com cada rotina claramente separada.</p>
        </div>
        <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-file-earmark-spreadsheet"></i></span>
      </header>
      <!-- Primeiro Card -->
      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card p-4">
            <h4 class="mb-4"><i class="bi bi-arrow-bar-up"></i> Importar Excel de Pedidos</h4>
            <form method="POST" action="../admin/process_upload.php" enctype="multipart/form-data" id="uploadExcelForm">
              <div class="row mb-3">
                <div class="col-md-5">
                  <label class="form-label">Selecione o Excel</label>
                  <input name="excel_file" id="nomeexcel" class="form-control mandaexcel" type="file">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Mesas</label>
                  <input name="mesas" id="qntmesas" class="form-control mandaexcel" type="number" placeholder="Número de mesas">
                </div>
                <div class="col-md-2">
                  <label class="form-label">Caixa final</label>
                  <input name="caixa_final" id="cxfinal" class="form-control mandaexcel" type="number" placeholder="Valor final do caixa">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                  <button class="btn btn-warning w-80" id="btnenviar" type="submit">Enviar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <!-- Segundo Card
      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card p-4" style="background-color: #feecb6ff;;">
            <h4 class="mb-4"><i class="bi bi-arrow-bar-up"></i> Importar Excel Caixa</h4>
            <form method="POST" action="../admin/process_caixaupload.php" enctype="multipart/form-data" id="uploadExcelFormCaixa">
              <div class="row mb-3">
                <div class="col-md-10">
                  <label class="form-label">Selecione o Excel</label>
                  <input name="excel_file"  class="form-control mandaexcel" type="file">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-warning w-80"  type="submit">Enviar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>-->

      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card p-4">
            <h4 class="mb-4"><i class="bi bi-arrow-bar-up"></i> Importar Excel Clientes Ativos</h4>
            <form method="POST" action="../admin/process_active-client-upload.php" enctype="multipart/form-data" id="uploadExcelFormClientactive">
              <div class="row mb-3">
                <div class="col-md-10">
                  <label class="form-label">Selecione o Excel</label>
                  <input name="excel_file"  class="form-control mandaexcel" type="file">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-warning w-80"  type="submit">Enviar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>


      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card p-4">
            <h4 class="mb-4"><i class="bi bi-arrow-bar-up"></i> Importar Excel Clientes Inativos</h4>
            <form method="POST" action="../admin/process_inactive-client-upload.php" enctype="multipart/form-data" id="uploadExcelFormClientinactive">
              <div class="row mb-3">
                <div class="col-md-10">
                  <label class="form-label">Selecione o Excel</label>
                  <input name="excel_file"  class="form-control mandaexcel" type="file">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-warning w-80"  type="submit">Enviar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="row mb-4">
        <div class="col-md-12">
          <div class="card p-4">
            <h4 class="mb-4"><i class="bi bi-arrow-bar-up"></i> Importar Excel Clientes em Potencial</h4>
            <form method="POST" action="../admin/process_potential-client-upload.php" enctype="multipart/form-data" id="uploadExcelFormClientpotential">
              <div class="row mb-3">
                <div class="col-md-10">
                  <label class="form-label">Selecione o Excel</label>
                  <input name="excel_file"  class="form-control mandaexcel" type="file">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                  <button class="btn btn-warning w-80"  type="submit">Enviar</button>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>






<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


<script src="../../assets/js/admin/uploads/formulario-pedidos.js"></script>



<script src="../../assets/js/admin/uploads/enviar-pedidos.js"></script>


<script src="../../assets/js/admin/uploads/enviar-caixa.js"></script>



<script src="../../assets/js/admin/uploads/enviar-clientes-ativos.js"></script>


<script src="../../assets/js/admin/uploads/enviar-clientes-inativos.js"></script>



<script src="../../assets/js/admin/uploads/enviar-clientes-potenciais.js"></script>


</body>
</html>
