<?php
session_start();
$mysqli = include __DIR__ . '/../banco/conectar.php';

header('Content-Type: application/json; charset=UTF-8');


if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['usuario_id'])) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Requisição inválida']);
    exit;
}



$nome = $_POST['nome'] ?? null;
$email = $_POST['email'] ?? null;
$cpf = $_POST['cpf'] ?? null;
$telefone = $_POST['telefone'] ?? null;
$cargo = $_POST['cargo'] ?? null;
$tabela = $_POST['tabela'] ?? null;
$usuario_id = $_POST['usuario_id'] ?? null;

if (empty($tabela) || empty($usuario_id))
 {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Dados insuficientes']);
    exit;
}

try {
    if ($tabela === 'contribuidor') {
          $stmt = $mysqli->prepare("UPDATE contribuidor SET nome=?, CPF=?, Telefone=?, Cargo=? WHERE usuario_id=?");
        $stmt->bind_param("ssssi", $nome, $cpf, $telefone, $cargo, $usuario_id);
        $ok1 = $stmt->execute();
        $stmt->close();



        if (!empty($email)) {
           $stmt2 = $mysqli->prepare("UPDATE usuario SET email=? WHERE usuario_id=?");
            $stmt2->bind_param("si", $email, $usuario_id);
            $ok2 = $stmt2->execute();
            $stmt2->close();

        } else {
            $ok2 = true;
        }

        if ($ok1 && $ok2) {
            echo json_encode(['ok' => true, 'message' => 'Cadastro alterado com sucesso']);
        } else {
            throw new Exception('Erro ao atualizar contribuidor');
        }
    }

    elseif ($tabela === 'motoboy') {
         $stmt = $mysqli->prepare("UPDATE motoboy SET nome=?, CPF=?, Telefone=? WHERE usuario_id=?");
        $stmt->bind_param("sssi", $nome, $cpf, $telefone, $usuario_id);
        $ok1 = $stmt->execute();
        $stmt->close();
         // Atualiza pagmot
        $stmt2 = $mysqli->prepare("UPDATE pagmot SET nome=? WHERE usuario_id=?");
        $stmt2->bind_param("si", $nome, $usuario_id);
        $ok2 = $stmt2->execute();
        $stmt2->close();

        if (!empty($email)) {
              $stmt3 = $mysqli->prepare("UPDATE usuario SET email=? WHERE usuario_id=?");
            $stmt3->bind_param("si", $email, $usuario_id);
            $ok3 = $stmt3->execute();
            $stmt3->close();

        } else {
            $ok3     = true;
        }
           if ($ok1 && $ok2 && $ok3) {
          echo json_encode(['ok' => true, 'message' => 'Cadastro alterado com sucesso']);
             exit;
        } else {
            throw new Exception('Erro ao atualizar motoboy');
        exit;
        }


    }

    elseif ($tabela === 'admin') {
        $stmt = $mysqli->prepare("UPDATE admin SET nome=? WHERE usuario_id=?");
        $stmt->bind_param("si", $nome, $usuario_id);
        $ok1 = $stmt->execute();
        $stmt->close();

        if (!empty($email)) {
          $stmt = $mysqli->prepare("UPDATE admin SET nome=?, CPF=?, Telefone=?, Cargo=? WHERE usuario_id=?");
        $stmt->bind_param("ssssi", $nome, $cpf, $telefone, $cargo, $usuario_id);
        $ok1 = $stmt->execute();
        $stmt->close();
        } else {
            $ok2 = true;
        }

      if ($ok1 && $ok2) {
           echo json_encode(['ok' => true, 'message' => 'Cadastro alterado com sucesso']);
             exit;
        } else {
          throw new Exception('Erro ao atualizar admin');
        }


    }

    elseif ($tabela === 'usuario') {
        $stmt = $mysqli->prepare("UPDATE usuario SET email=?, tipo=? WHERE usuario_id=?");
        $stmt->bind_param("ssi", $email, $cargo, $usuario_id);

        if ($stmt->execute()) {
            echo json_encode(['ok' => true, 'message' => 'Cadastro alterado com sucesso']);
        } else {
            throw new Exception('Erro ao atualizar usuário');
        }
        $stmt->close();
    }

    else {
        throw new Exception('Tabela não reconhecida');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => $e->getMessage()]);
}
?>
