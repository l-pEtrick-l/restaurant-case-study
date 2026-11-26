<?php
session_start();
if (!isset($_SESSION['logado']) || $_SESSION['logado'] !== true) {
    header('Location: ../../index.php');
    exit;
}
if (($_SESSION['tipo'] ?? '') !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

$mysqli = include __DIR__ . '/../banco/conectar.php';
$_SESSION['csrf_solicitacoes'] = bin2hex(random_bytes(32));

$pedidos = [];
$resultadoPedidos = $mysqli->query(
    "SELECT pr.ID_PR, pr.usuario_id, pr.condicao, pr.data_PR, pr.observacao,
            COALESCE(
                CONVERT(adm.nome USING utf8mb4) COLLATE utf8mb4_unicode_ci,
                CONVERT(con.nome USING utf8mb4) COLLATE utf8mb4_unicode_ci,
                CONVERT(mot.nome USING utf8mb4) COLLATE utf8mb4_unicode_ci,
                CONVERT(u.email USING utf8mb4) COLLATE utf8mb4_unicode_ci,
                CONCAT(_utf8mb4'Usuário #' COLLATE utf8mb4_unicode_ci, pr.usuario_id)
            ) AS solicitante,
            (SELECT COUNT(*) FROM PED_ITM pi WHERE pi.ID_PR = pr.ID_PR) AS total_itens
       FROM PED_REP pr
       LEFT JOIN usuario u ON u.usuario_id = pr.usuario_id
       LEFT JOIN admin adm ON adm.usuario_id = pr.usuario_id
       LEFT JOIN contribuidor con ON con.usuario_id = pr.usuario_id
       LEFT JOIN motoboy mot ON mot.usuario_id = pr.usuario_id
      ORDER BY pr.data_PR DESC, pr.ID_PR DESC"
);
while ($pedido = $resultadoPedidos->fetch_assoc()) {
    $pedidos[] = $pedido;
}

$itensPorPedido = [];
$resultadoItens = $mysqli->query(
    'SELECT pi.ID_PR, pi.quantidade, pi.observacao, i.nome, i.unidade, i.origem, c.categoria
       FROM PED_ITM pi
       JOIN ITENS i ON i.ID_ITM = pi.ID_ITM
       JOIN CATEGORIA c ON c.ID_CAT = i.ID_CAT
      ORDER BY pi.ID_PR DESC, i.origem, c.categoria, i.nome'
);
while ($item = $resultadoItens->fetch_assoc()) {
    $itensPorPedido[(int) $item['ID_PR']][] = $item;
}

function quantidadePedido(string $valor): string
{
    $formatado = number_format((float) $valor, 3, ',', '.');
    return rtrim(rtrim($formatado, '0'), ',');
}

function classeCondicao(string $condicao): string
{
    $valor = mb_strtolower(trim($condicao));
    if (in_array($valor, ['concluído', 'concluido', 'atendido', 'finalizado'], true)) {
        return 'status-success';
    }
    if (in_array($valor, ['cancelado', 'recusado'], true)) {
        return 'status-danger';
    }
    if (in_array($valor, ['em andamento', 'separando', 'processando'], true)) {
        return 'status-info';
    }
    return 'status-warning';
}

$hoje = date('Y-m-d');
$totalSolicitado = 0;
$totalHoje = 0;
$condicoes = [];
$detalhesPedidos = [];

foreach ($pedidos as $pedido) {
    $idPedido = (int) $pedido['ID_PR'];
    $dataPedido = new DateTime($pedido['data_PR']);
    $itensPedido = $itensPorPedido[$idPedido] ?? [];
    if (mb_strtolower($pedido['condicao']) === 'solicitado') {
        $totalSolicitado++;
    }
    if ($dataPedido->format('Y-m-d') === $hoje) {
        $totalHoje++;
    }
    $condicoes[$pedido['condicao']] = true;

    $detalhesPedidos[$idPedido] = [
        'id' => $idPedido,
        'solicitante' => $pedido['solicitante'],
        'condicao' => $pedido['condicao'],
        'data' => $dataPedido->format('d/m/Y \à\s H:i'),
        'observacao' => $pedido['observacao'] ?? '',
        'itens' => array_map(static function (array $item): array {
            return [
                'nome' => $item['nome'],
                'unidade' => $item['unidade'],
                'origem' => $item['origem'],
                'categoria' => $item['categoria'],
                'quantidade' => quantidadePedido((string) $item['quantidade']),
                'observacao' => $item['observacao'] ?? '',
            ];
        }, $itensPedido),
    ];
}
$condicoes['Finalizado'] = true;
ksort($condicoes);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>180 Foodhouse - Solicitações</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link rel="stylesheet" href="../../assets/css/admin/solicitacoes.css">
</head>
<body>
<?php include __DIR__ . '/navbar_admin.php'; ?>

<main class="container-fluid app-container py-3 py-lg-4">
    <header class="page-intro">
        <div><div class="eyebrow">ACOMPANHAMENTO</div><h1 class="page-title">Solicitações de reposição</h1><p class="page-subtitle">Consulte quem solicitou, quando o pedido foi feito e todos os itens enviados.</p></div>
        <a class="new-order-btn" href="reposicao.php"><i class="bi bi-plus-lg me-1"></i>Nova solicitação</a>
    </header>

    <section class="metric-grid" aria-label="Resumo das solicitações">
        <div class="metric-card"><span class="metric-icon"><i class="bi bi-clipboard-data"></i></span><div><strong><?= count($pedidos) ?></strong><span>solicitações registradas</span></div></div>
        <div class="metric-card"><span class="metric-icon"><i class="bi bi-hourglass-split"></i></span><div><strong id="totalSolicitado"><?= $totalSolicitado ?></strong><span>aguardando atendimento</span></div></div>
        <div class="metric-card"><span class="metric-icon"><i class="bi bi-calendar-check"></i></span><div><strong><?= $totalHoje ?></strong><span>criadas hoje</span></div></div>
    </section>

    <section class="requests-panel">
        <div class="filters">
            <div class="row g-2 align-items-end">
                <div class="col-lg-6"><label class="filter-label" for="buscaPedido">PESQUISAR</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span><input class="form-control filter-control border-start-0 ps-0" id="buscaPedido" placeholder="Número, solicitante ou item" autocomplete="off"></div></div>
                <div class="col-6 col-lg-3"><label class="filter-label" for="filtroCondicao">CONDIÇÃO</label><select class="form-select filter-control" id="filtroCondicao"><option value="">Todas</option><?php foreach (array_keys($condicoes) as $condicao): ?><option value="<?= htmlspecialchars($condicao) ?>"><?= htmlspecialchars($condicao) ?></option><?php endforeach; ?></select></div>
                <div class="col-6 col-lg-3"><label class="filter-label" for="filtroPeriodo">PERÍODO</label><select class="form-select filter-control" id="filtroPeriodo"><option value="">Todo o período</option><option value="today">Hoje</option><option value="7">Últimos 7 dias</option><option value="30">Últimos 30 dias</option></select></div>
            </div>
        </div>

        <div class="table-wrap requests-table">
            <table class="table table-hover">
                <thead class="table-dark"><tr><th>Solicitação</th><th>Solicitante</th><th>Itens</th><th>Condição</th><th class="text-end">Detalhes</th></tr></thead>
                <tbody>
                <?php foreach ($pedidos as $pedido):
                    $idPedido = (int) $pedido['ID_PR'];
                    $dataPedido = new DateTime($pedido['data_PR']);
                    $nomesItens = array_column($itensPorPedido[$idPedido] ?? [], 'nome');
                    $buscaPedido = mb_strtolower('#' . $idPedido . ' ' . $pedido['solicitante'] . ' ' . implode(' ', $nomesItens));
                ?>
                    <tr class="request-row" data-request-id="<?= $idPedido ?>" data-search="<?= htmlspecialchars($buscaPedido) ?>" data-status="<?= htmlspecialchars($pedido['condicao']) ?>" data-date="<?= $dataPedido->format('Y-m-d') ?>">
                        <td><div class="request-id">#<?= $idPedido ?></div><div class="request-date"><?= $dataPedido->format('d/m/Y \à\s H:i') ?></div></td>
                        <td><?= htmlspecialchars($pedido['solicitante']) ?></td>
                        <td><?= (int) $pedido['total_itens'] ?> <?= (int) $pedido['total_itens'] === 1 ? 'item' : 'itens' ?></td>
                        <td><span class="status-pill <?= classeCondicao($pedido['condicao']) ?>"><?= htmlspecialchars($pedido['condicao']) ?></span></td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-dark view-btn ver-pedido" type="button" data-pedido="<?= $idPedido ?>"><i class="bi bi-eye me-1"></i>Ver pedido</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mobile-request-list">
            <?php foreach ($pedidos as $pedido):
                $idPedido = (int) $pedido['ID_PR'];
                $dataPedido = new DateTime($pedido['data_PR']);
                $nomesItens = array_column($itensPorPedido[$idPedido] ?? [], 'nome');
                $buscaPedido = mb_strtolower('#' . $idPedido . ' ' . $pedido['solicitante'] . ' ' . implode(' ', $nomesItens));
            ?>
                <article class="request-card" data-request-card="<?= $idPedido ?>" data-search="<?= htmlspecialchars($buscaPedido) ?>" data-status="<?= htmlspecialchars($pedido['condicao']) ?>" data-date="<?= $dataPedido->format('Y-m-d') ?>">
                    <div class="request-card-top"><div><div class="request-id">Solicitação #<?= $idPedido ?></div><div class="request-date"><?= $dataPedido->format('d/m/Y \à\s H:i') ?></div></div><span class="status-pill <?= classeCondicao($pedido['condicao']) ?>"><?= htmlspecialchars($pedido['condicao']) ?></span></div>
                    <div class="request-card-meta"><span>Solicitante<strong><?= htmlspecialchars($pedido['solicitante']) ?></strong></span><span>Conteúdo<strong><?= (int) $pedido['total_itens'] ?> <?= (int) $pedido['total_itens'] === 1 ? 'item' : 'itens' ?></strong></span></div>
                    <button class="btn btn-outline-dark w-100 view-btn ver-pedido" type="button" data-pedido="<?= $idPedido ?>"><i class="bi bi-eye me-1"></i>Ver pedido completo</button>
                </article>
            <?php endforeach; ?>
        </div>

        <div class="empty-state" id="semPedidos"><i class="bi bi-inbox"></i><strong>Nenhuma solicitação encontrada</strong><div class="small mt-1">Ajuste os filtros ou crie uma nova solicitação.</div></div>
        <div class="pagination-bar">
            <span id="resumoPagina">0 solicitações</span>
            <div class="pagination-controls d-flex gap-2"><button class="page-button" type="button" id="paginaAnterior"><i class="bi bi-chevron-left"></i> Anterior</button><button class="page-button" type="button" id="proximaPagina">Próxima <i class="bi bi-chevron-right"></i></button></div>
        </div>
    </section>
</main>

<div class="modal fade" id="modalPedido" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
        <div class="detail-hero">
            <div class="d-flex align-items-start justify-content-between gap-3"><div><div class="eyebrow">DETALHES DO PEDIDO</div><h2 class="h4 fw-bold mb-0" id="tituloPedido">Solicitação</h2></div><button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Fechar"></button></div>
            <div class="detail-meta" id="metaPedido"></div>
        </div>
        <div class="modal-body p-3 p-lg-4">
            <div id="observacaoPedidoDetalhe"></div>
            <div class="d-flex align-items-center justify-content-between mt-3 mb-2"><h3 class="h6 fw-bold mb-0">Itens solicitados</h3><span class="badge text-bg-light" id="totalItensPedido"></span></div>
            <div class="detail-items" id="itensPedidoDetalhe"></div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-light px-4" data-bs-dismiss="modal">Fechar</button>
            <button type="button" class="btn btn-success px-4" id="finalizarPedido"><i class="bi bi-check2-circle me-1"></i>Marcar como finalizado</button>
        </div>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const pedidos = <?= json_encode($detalhesPedidos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
const csrfSolicitacoes = <?= json_encode($_SESSION['csrf_solicitacoes']) ?>;
</script>
<script src="../../assets/js/admin/solicitacoes.js"></script>
</body>
</html>
