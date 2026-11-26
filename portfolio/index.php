<?php

session_start();

$erro = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (!empty($email) && !empty($senha)) {
        $mysqli = require __DIR__ . '/php/banco/conectar.php';
        $stmt = $mysqli->prepare("SELECT usuario_id, senha, tipo FROM usuario WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 1) {
            $usuario = $res->fetch_assoc();

            if (password_verify($senha, $usuario['senha'])) {
                $_SESSION['usuario'] = $email;
                $_SESSION['usuario_id'] = $usuario['usuario_id'];
                $_SESSION['logado'] = true;
                $_SESSION['tipo'] = $usuario['tipo'];

                // Buscar nome de acordo com tipo
                if ($usuario['tipo'] === 'admin') {
                    $nomeStmt = $mysqli->prepare("SELECT nome FROM admin WHERE usuario_id = ?");
                } elseif ($usuario['tipo'] === 'motoboy') {
                    $nomeStmt = $mysqli->prepare("SELECT nome FROM motoboy WHERE usuario_id = ?");
                }

                if (isset($nomeStmt)) {
                    $nomeStmt->bind_param("i", $_SESSION['usuario_id']);
                    $nomeStmt->execute();
                    $nomeRes = $nomeStmt->get_result();

                    if ($row = $nomeRes->fetch_assoc()) {
                        $_SESSION['nome'] = $row['nome'];
                    } else {
                        $_SESSION['nome'] = ucfirst($usuario['tipo']);
                    }
                    $nomeStmt->close();
                }

                // Redireciona
                if ($usuario['tipo'] === 'admin') {
                    header("Location: /php/admin/inicial.php");
                } elseif ($usuario['tipo'] === 'motoboy') {
                    header("Location: /php/user/inicial.php");
                } else {
                    $erro = "Tipo de usuário não reconhecido.";
                }

                exit;
            } else {
                sleep(1);
                $erro = "Email ou senha inválidos.";
            }
        } else {
            sleep(1);
            $erro = "Email ou senha inválidos.";
        }

        $stmt->close();
    } else {
        $erro = "Preencha todos os campos.";
    }

    if (isset($mysqli)) {
        $mysqli->close();
    }
}
?>

<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>180 Foodhouse - Faça Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="icon" type="image/png" href="/img/logofood.png">
    <link rel="stylesheet" href="assets/css/login.css">
</head>
<body>
    <div  class="container-fluid login-container">
        <div class="row h-100">
            <!-- COLUNA ESQUERDA -->
            <div id="esq" class="col-md-5 d-flex justify-content-center align-items-center bg-info">
                <div class="login-box p-4 bg-white rounded shadow">
                    <h4 class="mb-4 text-center">Entrar</h4>

                    <!-- MOSTRAR ERRO -->
                    <?php if (!empty($erro)) : ?>
                        <div class="alert alert-danger text-center" role="alert"><?php echo $erro; ?></div>
                    <?php endif; ?>

                    <form action="" method="post">

                        <div class="mb-3">
                            <label class="form-label">Email</label>
                          <input type="email" id="emaill" class="form-control" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" id="senhaa" class="form-control" name="senha" required>
                        </div>
                        <button type="submit" id="botaoo" class="btn btn-secondary w-100">
                         <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="spinner"></span>
                        Entrar</button>
                    </form>
                </div>
            </div>

            <!-- COLUNA DIREITA (imagem) -->
            <div class="col-md-7 d-none d-md-flex align-items-center justify-content-center p-0">
                <img src="/img/buruger.webp" alt="Imagem" class="img-fluid login-img">
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
