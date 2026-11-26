<?php
session_start();

// Verificação de login e tipo de usuário
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header("Location: ../../index.php");
    exit;
} else if ($_SESSION['tipo'] !== 'admin') {
    echo "<script> alert('Acesso negado.'); window.location.href = '../../index.php'; </script>";
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
    <title>Dashboard - Configurações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/admin/configuracoes.css">
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
                <li class="nav-item"><a class="nav-link" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a></li>
                <li class="nav-item"><a class="nav-link active" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
                <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
                 <li class="nav-item"><a class="nav-link " href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
            </ul>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- SIDEBAR -->
            <div class="col-md-2 sidebar d-none d-md-block">
                <ul class="nav flex-column">
                    <li class="nav-item"><a class="nav-link" href="../admin/pagamento.php"><i class="bi bi-card-checklist"></i> Lista de Pagamentos</a></li>
                    <li class="nav-item"><a class="nav-link active" href="../admin/configuracoes.php"><i class="bi bi-gear"></i> Configurações</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/fechamentodiario.php"><i class="bi bi-file-earmark-medical"></i> Fechamento diario</a></li>
                    <li class="nav-item"><a class="nav-link" href="../admin/uploadarquivos.php"><i class="bi bi-arrow-bar-up"></i> Upload de Arquivos</a></li>
                     <li class="nav-item"><a class="nav-link" href="../admin/controle_caixa.php"><i class="bi bi-cash-coin"></i> Controle de Caixa</a></li>
                </ul>
            </div>

            <!-- CONTEÚDO PRINCIPAL -->
            <div class="col-md-10 content module-content">
                <header class="module-page-header">
                    <div class="module-page-copy">
                        <span class="module-eyebrow"><i class="bi bi-sliders"></i> RELATÓRIOS</span>
                        <h1 class="module-page-title">Consultas e configurações</h1>
                        <p class="module-page-subtitle">Localize pedidos, registros manuais, regras gerais e campanhas dentro do período desejado.</p>
                    </div>
                    <span class="module-page-symbol" aria-hidden="true"><i class="bi bi-gear-wide-connected"></i></span>
                </header>

                <div class="card p-4 mb-4">
                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="datainicio" class="form-label">Data início:</label>
                                <input type="date" class="form-control" id="datainicio" name="datainicio" required>
                            </div>
                            <div class="col-md-4">
                                <label for="datafim" class="form-label">Data fim:</label>
                                <input type="date" class="form-control" id="datafim" name="datafim">
                            </div>
                            <div class="col-md-4">
                                <label for="nomeconsulta" class="form-label">Selecione a tabela:</label>
                                <select class="form-select" id="nomeconsulta" name="nomeconsulta" required>
                                    <option value="" selected disabled>Selecione...</option>
                                    <option value="excel_data">Pedidos</option>
                                    <option value="manual">Mesas e caixa final</option>
                                    <option value="rules">Regras Gerais</option>
                                    <option value="clients">Campanhas</option>
                                </select>
                            </div>
                        </div>
                        <button type="submit" id="botao" class="btn btn-submit w-100 disabled" disabled><i class="bi bi-search"></i> Buscar</button>
                    </form>
                </div>
            </div>




        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="../../assets/js/admin/relatorios/filtro-periodo.js"></script>

    <script src="../../assets/js/admin/relatorios/salvar-edicao.js"></script>
    <script src="../../assets/js/admin/relatorios/edicao-em-lote.js"></script>

    <?php
    // Limpar sessão
    unset($_SESSION['datainicio'], $_SESSION['datafim'], $_SESSION['nomeconsulta']);
    ?>
</body>

</html>
