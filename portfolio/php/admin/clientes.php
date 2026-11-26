<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
  echo "<script>alert('Usuário não identificado.');</script>";
  exit;
}

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_active
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_active)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_ativos = $row['total'];
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_inactive
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_inactive)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_inativos = $row['total']; // <-- variável certa
$stmt->close();

$stmt = $mysqli->prepare("
    SELECT COUNT(*) AS total
    FROM excel_client_potential
    WHERE data_insercao = (SELECT MAX(data_insercao) FROM excel_client_potential)
      AND nome_cliente IS NOT NULL
      AND numero_telefone IS NOT NULL
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$quantidade_clientes_potencial = $row['total']; // <-- variável certa
$stmt->close();


$stmt = $mysqli->prepare("SELECT * FROM clients ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();
$campanhas = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$mysqli->close();
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Relação de clientes</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/admin/clientes.css">
  <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>

<body class="admin-module-page clients-page">
  <?php
  if (isset($_SESSION['mensagem'])) {
    echo "<script>alert('{$_SESSION['mensagem']}');</script>";
    unset($_SESSION['mensagem']);
  }
  ?>

  <?php include __DIR__ . '/navbar_admin.php'; ?>


  <div class="offcanvas offcanvas-start" tabindex="-1" id="menuLateral">
    <div class="offcanvas-header">
      <h5 class="offcanvas-title">Menu</h5>
      <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body sidebar">
      <ul class="nav flex-column">
        <li class="nav-item">
          <a class="nav-link" href="../admin/txs.php"><i class="bi bi-scooter"></i> Taxas do Motoboy</a>

        </li>
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="bi bi-card-checklist"></i> Ficha Tecnica</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="../admin/clientes.php"><i class="bi bi-bag-check"></i> Clientes</a>
        </li>
      </ul>
    </div>
  </div>

  <div class="container-fluid">
    <div class="row">
      <!-- SIDEBAR -->
      <div class="col-md-2 sidebar d-none d-md-block">
        <ul class="nav flex-column">
          <li class="nav-item">
            <a class="nav-link" href="../admin/txs.php"><i class="bi bi-scooter"></i> Taxas do Motoboy</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-card-checklist"></i> Ficha Técnica</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="../admin/clientes.php"><i class="bi bi-bag-check"></i> Relação de Clientes</a>
          </li>
        </ul>
      </div>

      <!-- CONTEÚDO PRINCIPAL -->
      <div class="col-md-10 content module-content">
        <header class="module-page-header">
          <div class="module-page-copy">
            <span class="module-eyebrow"><i class="bi bi-people"></i> LOGÍSTICA</span>
            <h1 class="module-page-title">Campanhas e clientes</h1>
            <p class="module-page-subtitle">Crie comunicações, selecione o público e acompanhe as campanhas preparadas para transmissão.</p>
          </div>
          <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-chat-heart"></i></span>
        </header>

        <div class="card p-4">
          <div class="tabs">
            <div class="tab active" data-target="create">Criar Campanha</div>
            <div class="tab" data-target="view">Transmitir Campanha</div>
          </div>
          <div class="content">
            <div id="create" class="content-section active">
              <div class="row">
                <!-- LADO ESQUERDO (formulário) -->
                <div class="col-md-6 border-end">
                  <form id="formzap" method="POST" enctype="multipart/form-data" action="process_whatsapp_msg_upload.php">
                    <!-- Foto -->
                    <div class="mb-3">
                      <label class="form-label" for="imgzap"><i class="bi bi-camera"></i> Selecione sua foto</label>
                      <input type="file" class="form-control" id="imgzap" name="imgzap" accept="image/*">
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="titulotext"><i class="bi bi-cursor-text"></i> Nome da campanha:</label>
                      <input type="text" class="form-control" id="titulotext" name="titulotext" minlength="1" maxlength="255" placeholder="Digite o nome da campanha até 255 caracteres">
                    </div>

                    <div class="mb-3">
                      <label class="form-label" for="escopomsg"><i class="bi bi-card-text"></i> Texto da Mensagem</label>
                      <textarea class="form-control" id="escopomsg" name="escopomsg" rows="4" minlenght="1" maxlenght="255" placeholder="Escreva sua mensagem até 255 caracteres"></textarea>
                    </div>

                    <div class="row">
                      <div class="col-4">
                        <div class="mb-3">
                          <label class="form-label">Quais clientes receberão?</label>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="ActiveClient" name="Clients[]" value="ativo">
                            <label class="form-check-label" for="ActiveClient"><i class="bi bi-person-check"></i> Clientes Ativos</label>
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="InactiveClient" name="Clients[]" value="inativo">
                            <label class="form-check-label" for="InactiveClient"><i class="bi bi-person-exclamation"></i> Clientes Inativos</label>


                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="PotentialClient" name="Clients[]" value="potencial">
                            <label class="form-check-label" for="PotentialClient"><i class="bi bi-person-add"></i> Potencial Cliente</label>
                          </div>
                        </div>
                      </div>

                      <div class="col">
                        <div class="mb-3">
                          <label class="form-label">Quantos Clientes receberão essa mensagem?</label>
                          <div class="form-check">
                            <input class="form-check-input" type="number" id="quantidadeativo" name="quantidadeativo" min="1" max="255" placeholder="Até <?= $quantidade_clientes_ativos ?> clientes ativos" style="width: 200px; height: auto;">
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="number" id="quantidadeinativo" name="quantidadeinativo" min="1" max="255" placeholder="Até <?= $quantidade_clientes_inativos ?> clientes inativos" style="width: 200px; height: auto;">
                          </div>
                          <div class="form-check">
                            <input class="form-check-input" type="number" id="quantidadepotencial" name="quantidadepotencial" min="1" max="255" placeholder="Até <?= $quantidade_clientes_potencial ?> clientes potencial" style="width: 200px; height: auto;">
                          </div>
                        </div>
                      </div>

                      <div class="col-md-3">
                        <label class="form-label">Clientes disponíveis</label>
                        <div class="d-flex flex-column gap-2">
                          <input class="form-control" disabled value="<?= $quantidade_clientes_ativos ?> ativos">
                          <input class="form-control" disabled value="<?= $quantidade_clientes_inativos ?> inativos">
                          <input class="form-control" disabled value="<?= $quantidade_clientes_potencial ?> potencial">
                        </div>
                      </div>



                      <!-- Botão -->
                      <button type="submit" id="botao" class="btn btn-success w-100">
                        <i class="bi bi-whatsapp"></i> Criar campanha para o WhatsApp
                      </button>
                  </form>
                </div>
              </div>

              <!-- LADO DIREITO (preview estilo WhatsApp) -->
              <div class="col-md-6 d-flex justify-content-center align-items-center">
                <div class="w-100 p-3" style="background-image: url(../../img/fundozap.jpg); border-radius: 10px;">
                  <div class="d-flex flex-column align-items-end">

                    <!-- Balão de mensagem com bico -->
                    <div id="previewMessage" class="whatsapp-bubble p-2">
                      <div class="d-flex justify-content-center">
                        <img id="previewImage" src="" alt="Prévia da imagem"
                          class="img-fluid rounded mb-2"
                          style="display: none; max-height: 200px;">
                      </div>
                      <p id="previewText" class="mb-1 text-start" style="white-space: pre-wrap;"></p>

                      <!-- Rodapé: horário + tique azul -->
                      <div class="text-end small text-muted d-flex justify-content-end align-items-center gap-1">
                        <span id="msgTime">14:22</span>
                        <i class="bi bi-check2-all text-primary" style="font-size: 1rem;"></i>
                      </div>
                    </div>

                  </div>
                </div>
              </div>
            </div>
          </div>
          <div id="view" class="content-section">
            <div class="row">
              <!-- Lista de campanhas -->
              <div class="col-md-4 border-end">
                <h5 class="mb-3">Campanhas salvas</h5>
                <div class="mt-3">
                  <button id="btnTransmitir" class="btn btn-success w-100">
                    <i class="bi bi-send-fill"></i> Enviar Transmissão
                  </button>
                </div>

                <ul class="list-group" id="listaCampanhas">
                  <?php foreach ($campanhas as $camp): ?>
                    <li class="list-group-item list-group-item-action"
                      data-nome="<?= htmlspecialchars($camp['campanha']) ?>"
                      data-msg="<?= htmlspecialchars($camp['mensagem']) ?>"
                      data-img="<?= htmlspecialchars($camp['imagem']) ?>">
                      <?= htmlspecialchars($camp['campanha']) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              </div>


              <!-- Visualização da campanha -->
              <div class="col-md-8 d-flex justify-content-center align-items-center">
                <div class="w-100 p-3" style="background-image: url(../../img/fundozap.jpg); border-radius: 10px;">
                  <div class="d-flex flex-column align-items-end">

                    <div id="previewMessageView" class="whatsapp-bubble p-2" style="display:none;">
                      <div class="d-flex justify-content-center">
                        <img id="previewImageView" src="" alt="Prévia da imagem"
                          class="img-fluid rounded mb-2" style="max-height: 200px;">
                      </div>
                      <p id="previewTextView" class="mb-1 text-start" style="white-space: pre-wrap;"></p>
                      <div class="text-end small text-muted d-flex justify-content-end align-items-center gap-1">
                        <span id="msgTimeView">14:22</span>
                        <i class="bi bi-check2-all text-primary" style="font-size: 1rem;"></i>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Scripts -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/admin/clientes/transmissao.js"></script>

  <script src="../../assets/js/admin/clientes/campanhas.js"></script>

  <script src="../../assets/js/admin/clientes/preview-mensagem.js"></script>

  <script src="../../assets/js/admin/clientes/publico.js"></script>


  <script src="../../assets/js/admin/clientes/formulario-mensagem.js"></script>

  <script src="../../assets/js/admin/clientes/abas.js"></script>

</body>

</html>
