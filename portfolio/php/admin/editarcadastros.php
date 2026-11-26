<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
} else if ($_SESSION['tipo'] !== 'admin') {
    echo "<script> alert('Acesso negado.'); window.location.href = '../../index.php'; </script>";
    exit;
}
$mysqli = include __DIR__ . '/../banco/conectar.php';

$sql = "
SELECT
    u.usuario_id,
    COALESCE(c.nome, m.nome, a.nome) AS nome,
    u.email,
    COALESCE(c.CPF, m.CPF, a.CPF) AS CPF,
    COALESCE(c.Telefone, m.Telefone, a.Telefone) AS Telefone,
    u.tipo AS Cargo,
    CASE
        WHEN c.usuario_id IS NOT NULL THEN 'contribuidor'
        WHEN m.usuario_id IS NOT NULL THEN 'motoboy'
        WHEN a.usuario_id IS NOT NULL THEN 'admin'
        ELSE 'usuario'
    END AS tabela
FROM usuario u
LEFT JOIN contribuidor c ON c.usuario_id = u.usuario_id
LEFT JOIN motoboy m ON m.usuario_id = u.usuario_id
LEFT JOIN admin a ON a.usuario_id = u.usuario_id;

";
$banis = $mysqli->prepare($sql);
$banis->execute();
$ban = $banis->get_result();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Editar Cadastros</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="icon" type="image/png" href="../../img/logofood.png">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/editarcadastros.css">
    <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>


<body class="admin-module-page users-edit-page">
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
        <a class="nav-link" href="../admin/cadastros.php"><i class="bi bi-person-fill-add"></i> Cadastro de usuários</a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="../admin/editarcadastros.php"><i class="bi bi-person-fill-gear"></i> Editar Cadastros</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="bi bi-person-fill-slash"></i> Bloquear Cadastros</a>
      </li>
      </ul>
  </div>
</div>

<div class="d-flex">
  <!-- SIDEBAR -->
  <div class="col-md-2 sidebar d-none d-md-block" >
    <ul class="nav flex-column">
      <li class="nav-item">
        <a class="nav-link" href="../admin/cadastros.php"><i class="bi bi-person-fill-add"></i> Cadastro de usuários</a>
      </li>
      <li class="nav-item">
        <a class="nav-link active" href="../admin/editarcadastros.php"><i class="bi bi-person-fill-gear"></i> Editar Cadastros</a>
      </li>
      <li class="nav-item">
        <a class="nav-link" href="#"><i class="bi bi-person-fill-slash"></i> Bloquear Cadastros</a>
      </li>
    </ul>
  </div>
   <div class="container-fluid">
    <div class="col-md-10 content module-content">

    <header class="module-page-header">
      <div class="module-page-copy">
        <span class="module-eyebrow"><i class="bi bi-person-gear"></i> USUÁRIOS</span>
        <h1 class="module-page-title">Gestão de usuários</h1>
        <p class="module-page-subtitle">Pesquise cadastros e atualize os dados de acesso sem sair da listagem.</p>
      </div>
      <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-people-fill"></i></span>
    </header>

 <div class="card p-4 mb-4">
  <div class="table-responsive">
  <div class="module-filter-bar">
    <div class="col-12 col-lg-5">
      <label class="form-label" for="pesquisaNome">PESQUISAR USUÁRIO</label>
      <div class="input-group">
        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span>
        <input id="pesquisaNome" class="form-control border-start-0 ps-0" type="search" placeholder="Digite um nome" aria-label="Pesquisar usuário"/>
      </div>
    </div>
  </div>
      <table class="table table-striped table-hover tabela-edicao sem-edicao">
        <thead class="table-dark">
          <tr>
            <th>Nome</th>
            <th>Email</th>
            <th>CPF/CNPJ</th>
            <th>Telefone</th>
            <th>Atribuição</th>
            <th class="col-acoes">Ações</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $ban->fetch_assoc()): ?>
          <tr class="linha-edicao">

           <form method="POST" action="../admin/editarcadastros_api.php" class="meuFormulario">



              <td>
                <button type="button" class="btn btn-sm btn-outline-secondary toggle-edicao">✏️</button>
                <span class="valor-visivel"><?= htmlspecialchars($row['nome']) ?></span>
                <input type="text" class="form-control form-editar d-none" name="nome" value="<?= $row['nome'] ?>">
              </td>
              <td>
                <span class="valor-visivel"><?= htmlspecialchars($row['email'] ?? '-') ?></span>
                <input type="text" class="form-control form-editar d-none" name="email" value="<?= $row['email'] ?>">
              </td>
              <td>
                <span class="valor-visivel"><?= htmlspecialchars($row['CPF'] ?? '-') ?></span>
                <input type="text" class="form-control form-editar d-none" name="cpf" value="<?= $row['CPF'] ?>">
              </td>
              <td>
                <span class="valor-visivel"><?= htmlspecialchars($row['Telefone'] ?? '-') ?></span>
                <input type="text" class="form-control form-editar d-none" name="telefone" value="<?= $row['Telefone'] ?>">
              </td>
              <td>
              <input type="hidden" name="usuario_id" value="<?= $row['usuario_id'] ?? '' ?>">
                <span class="valor-visivel"><?= htmlspecialchars($row['Cargo'] ?? '-') ?></span>
                <input type="text" class="form-control form-editar d-none" name="cargo" value="<?= $row['Cargo'] ?>">
              </td>
              <td class="col-acoes">
                <input type="hidden" name="tabela" value="<?= $row['tabela'] ?>">
                <button type="submit" name="editar_linha" class="btn btn-primary btn-sm form-editar d-none">Salvar</button>
              </td>
            </form>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
       </div>
       </div>
    </div>
  </div>
</div>

<script src="../../assets/js/admin/editar-cadastros/modo-edicao.js"></script>
<script src="../../assets/js/admin/editar-cadastros/pesquisar.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>.
<script src="../../assets/js/admin/editar-cadastros/salvar.js"></script>

  <?php unset($_SESSION['datainicio'], $_SESSION['datafim'], $_SESSION['nomeconsulta']); ?>
</body>
</html>
