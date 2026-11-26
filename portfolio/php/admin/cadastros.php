<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
}else if ($_SESSION['tipo'] !== 'admin') {
    echo "<script>
    alert('Acesso negado.');
  window.location.href = '../../index.php';
    </script>";
    exit;
}



$mysqli = include __DIR__ . '/../banco/conectar.php';


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nome = $_POST['nome'] ?? '';
    $telefone = $_POST['telefone'] ?? '';
    $email = $_POST['email'] ?? '';
    $cpf = $_POST['CPF'] ?? '';
    $tipo = $_POST['tipo'] ?? '';
    $cargo = $_POST['cargo'] ?? '';

    $verificaEmail = $mysqli->prepare("SELECT usuario_id FROM usuario WHERE email = ?");
    $verificaEmail->bind_param("s", $email);
    $verificaEmail->execute();
    $verificaEmail->store_result();

    if ($tipo === 'admin' || $tipo === 'contribuidor' || $tipo === 'motoboy') {
        $tabela = $tipo;
        $verificaCPF = $mysqli->prepare("SELECT id FROM $tabela WHERE telefone = ?");
        $verificaCPF->bind_param("s", $telefone);
        $verificaCPF->execute();
        $verificaCPF->store_result();
    } else {
        $_SESSION['mensagem'] = "Tipo de usuário inválido!";
        header("Location: ../admin/cadastros.php");
        exit;
    }

    if ($verificaEmail->num_rows > 0 || $verificaCPF->num_rows > 0) {
        echo "<script>alert('E-mail ou telefone já estão cadastrados!');</script>";
        exit;
    }

    $array = explode(" ", $nome);
    $primeiro_nome = $array[0];
    if (!empty($cpf)) {
        $senha_gerada = $primeiro_nome . substr($cpf, 0, 3);
    } else {
        $senha_gerada = $primeiro_nome . substr($telefone, -3);
    }
    $senha_hash = password_hash($senha_gerada, PASSWORD_DEFAULT);


    $inserirUsuario = $mysqli->prepare("INSERT INTO usuario (email, senha, tipo) VALUES (?, ?, ?)");
    $inserirUsuario->bind_param("sss", $email, $senha_hash, $tipo);

    if ($inserirUsuario->execute()) {
        $usuario_id = $inserirUsuario->insert_id;
$query = "INSERT INTO $tabela (usuario_id, nome, cpf, telefone";
$values = "?, ?, ?, ?";
$types = "isss";

if ($tipo !== 'motoboy') {
    $query .= ", cargo";
    $values .= ", ?";
    $types .= "s";
}

$query .= ") VALUES ($values)";

        $stmt = $mysqli->prepare($query);

        if ($tipo !== 'motoboy') {
            $stmt->bind_param($types, $usuario_id, $nome, $cpf, $telefone, $cargo);
        } else {
            $stmt->bind_param($types, $usuario_id, $nome, $cpf, $telefone);
        }

        if ($stmt->execute()) {
            $_SESSION['mensagem'] = "$tipo cadastrado com sucesso!";
            header("Location: ../admin/cadastros.php");
            exit;
        } else {
             $_SESSION['mensagem'] = "Erro ao cadastrar na tabela $tabela";
            header("Location: ../admin/cadastros.php");
            exit;
        }
    } else {
            $_SESSION['mensagem'] = "Erro ao cadastrar o usuario";
            header("Location: ../admin/cadastros.php");
            exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Cadastros</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/cadastros.css">
    <link href="admin-modules.css?v=20260824-2" rel="stylesheet">
</head>

<body class="admin-module-page users-create-page">
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
                        <a class="nav-link active" href="../admin/cadastros.php"><i class="bi bi-person-fill-add"></i>Cadastro de usuarios</a>
                        </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/editarcadastros.php"><i class="bi bi-person-fill-gear"></i> Editar Cadastros</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-person-fill-slash"></i> Bloquear Cadastros</a>
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
                        <a class="nav-link active" href="../admin/cadastros.php"><i class="bi bi-person-fill-add"></i>Cadastro de usuarios</a>
                        </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../admin/editarcadastros.php"><i class="bi bi-person-fill-gear"></i> Editar Cadastros</a>
                    </li>
                      <li class="nav-item">
                        <a class="nav-link" href="#"><i class="bi bi-person-fill-slash"></i> Bloquear Cadastros</a>
                    </li>
                </ul>
            </div>

            <!-- CONTEÚDO PRINCIPAL -->
            <div class="col-md-10 content module-content">
                <header class="module-page-header">
                    <div class="module-page-copy">
                        <span class="module-eyebrow"><i class="bi bi-shield-check"></i> USUÁRIOS</span>
                        <h1 class="module-page-title">Cadastro de usuários</h1>
                        <p class="module-page-subtitle">Crie acessos para motoboys, administradores e contribuidores de forma simples e segura.</p>
                    </div>
                    <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-person-plus"></i></span>
                </header>

                <div class="card p-4">
                    <div class="form-choice-bar mb-4">
                        <label class="form-label">Tipo de usuário:</label>
                        <select class="form-select" id="tipoUsuario" onchange="mostrarFormulario()">
                            <option value="" selected disabled>Escolha uma opção</option>
                            <option value="motoboy">Motoboy</option>
                            <option value="admin">Admin</option>
                            <option value="contribuidor">Contribuidor</option>
                        </select>
                    </div>

                    <form id="formCadastro" method="post">
                        <div id="camposMotoboy" class="user-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" name="CPF">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone">
                            </div>
                            <input type="hidden" name="tipo" value="motoboy">
                        </div>

                        <div id="camposAdmin" class="user-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" name="CPF">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Atribuição</label>
                                <input type="text" class="form-control" name="cargo">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone">
                            </div>
                            <input type="hidden" name="tipo" value="admin">
                        </div>

                        <div id="camposContribuidor" class="user-fields" style="display: none;">
                            <div class="mb-3">
                                <label class="form-label">Nome</label>
                                <input type="text" class="form-control" name="nome">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">CPF/CNPJ</label>
                                <input type="text" class="form-control" name="CPF">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefone</label>
                                <input type="text" class="form-control" name="telefone">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="text" class="form-control" name="email">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Atribuição</label>
                                <input type="text" class="form-control" name="cargo">
                            </div>
                            <input type="hidden" name="tipo" value="contribuidor">
                        </div>

                        <button type="submit" id="botao" class="btn btn-submit w-100">Cadastrar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<!-- Dependências -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/imask"></script>

<script src="../../assets/js/admin/cadastros/formularios.js"></script>

</body>
</html>
