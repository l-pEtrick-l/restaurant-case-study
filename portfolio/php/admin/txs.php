

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

$usuario_id = $_SESSION['usuario_id'] ?? null;
if (!$usuario_id) {
    echo "<script>alert('Usuário não identificado.');</script>";
    exit;
}

if($_SERVER["REQUEST_METHOD"] === "POST") {
    header('Content-Type: application/json; charset=UTF-8');
        ob_start();
$nome = $_POST['nome_motoboy'] ?? '';
$entradas = $_POST['entradas'] ?? 0;
$taxas = $_POST['taxas'] ?? 0;
$saidas = $_POST['saidas'] ?? 0;
$dat = $_POST['data'] ?? '';
$diaria = $_POST['diaria'] ?? '';

$taxas = max($taxas, 30);

if($diaria === "pago"){
  $gasolina = "pago";
}else{
$gasolina = $_POST['gasolina'] ?? '';
}
  $usuario_idd = 0;
    $ban= $mysqli->prepare("SELECT id FROM motoboy WHERE nome = ?");
    $ban->bind_param("s", $nome);
    $ban->execute();
    $res = $ban->get_result();


    $bann = $mysqli->prepare("SELECT usuario_id FROM motoboy WHERE nome = ?");
    $bann->bind_param("s", $nome);
    $bann->execute();
    $bann->bind_result($usuario_idd);
    $bann->fetch();
    $bann->close();

    if ($res->num_rows > 0) {
        $banins = $mysqli->prepare("INSERT INTO pagmot (usuario_id, nome, taxas, saidas, entradas, dat, gasolina, diaria) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $banins->bind_param("isdddsss", $usuario_idd, $nome, $taxas, $saidas, $entradas, $dat, $gasolina, $diaria);
        $banins->execute();

      if ($banins->affected_rows > 0) {
            echo json_encode(['ok' => true, 'message' => 'Lançamento aprovado!']);
        } else {
            http_response_code(400);
            echo json_encode(['ok' => false, 'message' => 'Não foi possível realizar o lançamento.']);
        }

        $banins->close();
   } else {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => 'Motoboy não encontrado.']);
    }

$mysqli->close();
$ban->close();
 exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Lançamentos de Taxas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="../../assets/css/admin/txs.css">
  <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>

<body class="admin-module-page">
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
            <a class="nav-link active" href="../admin/txs.php"><i class="bi bi-scooter"></i> Taxas do Motoboy</a>

           </li>
          <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-card-checklist"></i> Ficha Tecnica</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="../admin/clientes.php"><i class="bi bi-bag-check"></i> Relação de Clientes</a>
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
            <a class="nav-link active" href="../admin/txs.php"><i class="bi bi-scooter"></i> Taxas do Motoboy</a>

           </li>
          <li class="nav-item">
            <a class="nav-link" href="#"><i class="bi bi-card-checklist"></i> Ficha Tecnica</a>
          </li>
          <li class="nav-item">
<a class="nav-link" href="../admin/clientes.php"><i class="bi bi-bag-check"></i> Relação de Clientes</a>
          </li>
        </ul>
      </div>

      <!-- CONTEÚDO PRINCIPAL -->
      <div class="col-md-10 content module-content">
        <header class="module-page-header">
          <div class="module-page-copy">
            <span class="module-eyebrow"><i class="bi bi-scooter"></i> LOGÍSTICA</span>
            <h1 class="module-page-title">Novo lançamento</h1>
            <p class="module-page-subtitle">Registre taxas, entradas, descontos, diária e gasolina em uma única etapa.</p>
          </div>
          <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-signpost-split"></i></span>
        </header>

        <div class="card p-4">
          <form method="post" action="" id="meuFormulario">
            <div class="row mb-3">
              <div class="col-md-6">
                <label for="motoboy" class="form-label">Nome do Motoboy</label>
                <input type="text" class="form-control" id="motoboy" name="nome_motoboy" required>
              </div>
              <div class="col-md-6">
                <label for="dataa" class="form-label">Data</label>
                <input type="date" class="form-control" id="dataa" name="data">
              </div>
            </div>

            <div class="row mb-3">
              <div class="col-md-4">
                <label for="entradas" class="form-label">Caixinhas / Entradas</label>
                <input type="number" step="any" class="form-control" id="entradas" name="entradas">
              </div>
              <div class="col-md-4">
                <label for="taxas" class="form-label">Taxas</label>
                <input type="number" step="any" class="form-control" id="taxas" name="taxas">
              </div>
              <div class="col-md-4">
                <label for="saidas" class="form-label">Descontos</label>
                <input type="number" step="any" class="form-control" id="saidas" name="saidas">
              </div>
            </div>

            <div class="row mb-4">
              <div class="col-md-6">
                <label class="form-label">Saldo do dia:</label><br>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="diaria" id="diariaPaga" value="pago" onclick="atualizarGasolina()">
                  <label class="form-check-label" for="diariaPaga">Pago</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="diaria" id="diariaNaoPaga" value="nao pago" onclick="atualizarGasolina()">
                  <label class="form-check-label" for="diariaNaoPaga">Não Pago</label>
                </div>
              </div>

              <div id="divgas" class="col-md-6 d-none">
                <label class="form-label">Gasolina</label><br>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="gasolina" id="gasPaga" value="pago">
                  <label class="form-check-label" for="gasPaga">Pago</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="gasolina" id="gasNaoPaga" value="nao pago">
                  <label class="form-check-label" for="gasNaoPaga">Não Pago</label>
                </div>
              </div>
            </div>

            <button type="submit" id="botao" class="btn btn-submit w-100"><i class="bi bi-check-circle"></i> Enviar Lançamento</button>
          </form>
        </div>
      </div>
    </div>
  </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/admin/taxas/formulario.js"></script>


 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="../../assets/js/admin/taxas/enviar.js"></script>

</body>
</html>
