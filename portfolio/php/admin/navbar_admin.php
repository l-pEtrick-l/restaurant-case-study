<?php
$arquivoAtual = basename($_SERVER['PHP_SELF'] ?? '');

$secaoPorArquivo = [
    'inicial.php' => 'inicio',
    'pagamento.php' => 'relatorios',
    'configuracoes.php' => 'relatorios',
    'fechamentodiario.php' => 'relatorios',
    'uploadarquivos.php' => 'relatorios',
    'controle_caixa.php' => 'relatorios',
    'txs.php' => 'logistico',
    'clientes.php' => 'logistico',
    'cadastros.php' => 'usuarios',
    'editarcadastros.php' => 'usuarios',
    'itens.php' => 'itens',
    'reposicao.php' => 'reposicao',
    'solicitacoes.php' => 'solicitacoes',
];

$secaoAtiva = $secaoPorArquivo[$arquivoAtual] ?? '';
$paginasComMenuLateral = [
    'pagamento.php',
    'configuracoes.php',
    'fechamentodiario.php',
    'uploadarquivos.php',
    'controle_caixa.php',
    'txs.php',
    'clientes.php',
    'cadastros.php',
    'editarcadastros.php',
];

$itensNavbar = [
    ['secao' => 'inicio', 'href' => 'inicial.php', 'icone' => 'bi-house-door', 'texto' => 'Início'],
    ['secao' => 'relatorios', 'href' => 'pagamento.php', 'icone' => 'bi-file-earmark-bar-graph', 'texto' => 'Relatórios'],
    ['secao' => 'logistico', 'href' => 'txs.php', 'icone' => 'bi-graph-up-arrow', 'texto' => 'Logístico'],
    ['secao' => 'usuarios', 'href' => 'cadastros.php', 'icone' => 'bi-people', 'texto' => 'Usuários'],
    ['secao' => 'itens', 'href' => 'itens.php', 'icone' => 'bi-box-seam', 'texto' => 'Itens'],
    ['secao' => 'reposicao', 'href' => 'reposicao.php', 'icone' => 'bi-cart-plus', 'texto' => 'Reposição'],
    ['secao' => 'solicitacoes', 'href' => 'solicitacoes.php', 'icone' => 'bi-clipboard-check', 'texto' => 'Solicitações'],
];
?>
<link rel="stylesheet" href="../../assets/css/admin/navbar-admin.css">

<nav class="navbar navbar-expand-xl navbar-dark sticky-top shadow-sm admin-topbar">
    <div class="container-fluid px-lg-4">
        <a class="navbar-brand" href="inicial.php"><img src="../../img/logofood.png" alt="180 Foodhouse"></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menuAdmin" aria-controls="menuAdmin" aria-expanded="false" aria-label="Abrir menu"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="menuAdmin">
            <ul class="navbar-nav me-auto gap-xl-1 mt-3 mt-xl-0">
                <?php foreach ($itensNavbar as $itemNavbar): ?>
                    <li class="nav-item"><a class="nav-link<?= $secaoAtiva === $itemNavbar['secao'] ? ' active' : '' ?>" href="<?= $itemNavbar['href'] ?>"><i class="bi <?= $itemNavbar['icone'] ?> me-1"></i><?= $itemNavbar['texto'] ?></a></li>
                <?php endforeach; ?>
            </ul>
            <form method="post" action="sair.php" class="my-3 my-xl-0"><button class="btn btn-outline-light btn-sm px-3 admin-logout" name="sair"><i class="bi bi-box-arrow-right me-1"></i>Sair</button></form>
        </div>
    </div>
</nav>

<?php if (in_array($arquivoAtual, $paginasComMenuLateral, true)): ?>
    <div class="container-fluid px-lg-4 d-md-none mt-2 admin-section-toggle-wrap">
        <button class="btn btn-warning admin-section-toggle" type="button" data-bs-toggle="offcanvas" data-bs-target="#menuLateral" aria-controls="menuLateral">
            <i class="bi bi-list-nested me-1"></i>Opções da página
        </button>
    </div>
<?php endif; ?>
