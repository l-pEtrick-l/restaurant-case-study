<?php

session_start();

$mysqli = include __DIR__ . '/php/banco/conectar.php';
$erro = '';
$email = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "E-mail inválido.";
    } elseif (!empty($email) && !empty($senha)) {
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

    $mysqli->close();
}
?>