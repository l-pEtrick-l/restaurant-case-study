<?php
session_start();
require_once __DIR__ . '/../config/ambiente.php';
$supportUrl = restaurantSupportUrl();
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

function totalPainel(mysqli $mysqli, string $sql): int
{
    $resultado = $mysqli->query($sql);
    return $resultado ? (int) $resultado->fetch_row()[0] : 0;
}

$itensAtivos = totalPainel($mysqli, 'SELECT COUNT(*) FROM ITENS WHERE ativo = 1');
$solicitacoesAbertas = totalPainel($mysqli, "SELECT COUNT(*) FROM PED_REP WHERE condicao = 'Solicitado'");
$solicitacoesHoje = totalPainel($mysqli, 'SELECT COUNT(*) FROM PED_REP WHERE DATE(data_PR) = CURRENT_DATE');
?>


<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>180 Foodhouse - Início</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
</head>

<link rel="stylesheet" href="../../assets/css/admin/inicial.css">

<body>

    <?php include __DIR__ . '/navbar_admin.php'; ?>

    <main class="container-fluid app-container py-3 py-lg-4">
        <section class="welcome-panel mb-4">
            <div class="welcome-content">
                <span class="eyebrow">PAINEL ADMINISTRATIVO</span>
                <h1 class="welcome-title">Olá, <?= htmlspecialchars($_SESSION['nome'] ?? 'administrador') ?>.</h1>
                <p class="welcome-text">Acompanhe a reposição e acesse as rotinas mais importantes sem procurar entre vários botões.</p>
                <div class="metrics" aria-label="Resumo do sistema">
                    <div class="metric"><span class="metric-value"><?= $solicitacoesAbertas ?></span><span class="metric-label">solicitações abertas</span></div>
                    <div class="metric"><span class="metric-value"><?= $solicitacoesHoje ?></span><span class="metric-label">solicitações hoje</span></div>
                    <div class="metric"><span class="metric-value"><?= $itensAtivos ?></span><span class="metric-label">itens ativos</span></div>
                </div>
            </div>
        </section>

        <section class="mb-4">
            <div class="mb-3"><div class="section-kicker">REPOSIÇÃO</div><h2 class="section-title">O que você precisa fazer?</h2></div>
            <div class="primary-actions">
                <a class="action-card primary" href="reposicao.php">
                    <i class="bi bi-arrow-right action-arrow"></i><span class="action-icon"><i class="bi bi-cart-plus"></i></span>
                    <h2>Nova solicitação</h2><p>Escolha os itens por categoria e monte um novo pedido de reposição.</p>
                </a>
                <a class="action-card" href="solicitacoes.php">
                    <i class="bi bi-arrow-right action-arrow"></i><span class="action-icon"><i class="bi bi-clipboard-check"></i></span>
                    <h2>Ver solicitações</h2><p>Consulte pedidos enviados, solicitantes, datas, observações e itens.</p>
                </a>
                <a class="action-card" href="itens.php">
                    <i class="bi bi-arrow-right action-arrow"></i><span class="action-icon"><i class="bi bi-box-seam"></i></span>
                    <h2>Gerenciar itens</h2><p>Cadastre, edite ou inative produtos do catálogo de reposição.</p>
                </a>
            </div>
        </section>

        <section class="tools-panel p-3 p-lg-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2 mb-3">
                <div><div class="section-kicker">OUTRAS FERRAMENTAS</div><h2 class="section-title">Administração</h2></div>
                <?php if ($supportUrl !== null): ?>
                    <a href="<?= htmlspecialchars($supportUrl, ENT_QUOTES, 'UTF-8') ?>" class="support-link text-decoration-none"><i class="bi bi-whatsapp me-1"></i>Preciso de ajuda</a>
                <?php endif; ?>
            </div>
            <div class="row g-2">
                <div class="col-md-4"><a class="tool-link" href="pagamento.php"><i class="bi bi-file-earmark-bar-graph"></i><span><strong>Relatórios</strong><small>Fechamentos e análises</small></span></a></div>
                <div class="col-md-4"><a class="tool-link" href="txs.php"><i class="bi bi-graph-up-arrow"></i><span><strong>Logístico</strong><small>Taxas e operação</small></span></a></div>
                <div class="col-md-4"><a class="tool-link" href="cadastros.php"><i class="bi bi-people"></i><span><strong>Usuários</strong><small>Cadastros e acessos</small></span></a></div>
            </div>
        </section>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
