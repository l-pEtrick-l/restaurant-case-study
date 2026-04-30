<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>180 Foodhouse - Faça Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
     <link rel="icon" type="image/png" href="IMGs/logofood.png">
     <link rel="stylesheet" href="CSS/style.css">
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

                    <form action="/PHP/config.php" method="POST">
                        
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                          <input type="email" id="email" class="form-control" name="email" required value="<?php echo htmlspecialchars($email ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Senha</label>
                            <input type="password" id="senha" class="form-control" name="senha" required>
                        </div>
                        <button type="submit" id="botao" class="btn btn-secondary w-100">
                         <span class="spinner-border spinner-border-sm me-2 d-none" role="status" aria-hidden="true" id="spinner"></span>    
                        Entrar</button>
                    </form>
                </div>
            </div>

            <!-- COLUNA DIREITA (imagem) -->
            <div class="col-md-7 d-none d-md-flex align-items-center justify-content-center p-0">
                <img src="/IMGs/index.png" alt="Imagem_inicial" class="img-fluid login-img">
            </div>
        </div>
    </div>
 <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
 <script src="JS/script.js"></script>
 </body>
</html>