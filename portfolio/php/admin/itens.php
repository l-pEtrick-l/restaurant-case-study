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
$_SESSION['csrf_itens'] = bin2hex(random_bytes(32));
$categoriasPermitidas = require __DIR__ . '/categorias_reposicao.php';
$unidadesPermitidas = require __DIR__ . '/unidades_itens.php';

$categoriasBanco = [];
$resultadoCategorias = $mysqli->query('SELECT ID_CAT, categoria FROM CATEGORIA ORDER BY categoria');
while ($categoria = $resultadoCategorias->fetch_assoc()) {
    $categoriasBanco[$categoria['categoria']] = $categoria;
}

$categoriasPorOrigem = [];
foreach ($categoriasPermitidas as $origem => $nomesCategorias) {
    foreach ($nomesCategorias as $nomeCategoria) {
        if (isset($categoriasBanco[$nomeCategoria])) {
            $categoriasPorOrigem[$origem][] = $categoriasBanco[$nomeCategoria];
        }
    }
}

$itens = [];
$resultadoItens = $mysqli->query(
    'SELECT i.ID_ITM, i.ID_CAT, i.nome, i.unidade, i.origem, i.ativo, c.categoria
       FROM ITENS i
       JOIN CATEGORIA c ON c.ID_CAT = i.ID_CAT
      ORDER BY i.origem, c.categoria, i.nome'
);
while ($item = $resultadoItens->fetch_assoc()) {
    $itens[] = $item;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>180 Foodhouse - Itens</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link rel="stylesheet" href="../../assets/css/admin/itens.css">
</head>
<body>
<?php include __DIR__ . '/navbar_admin.php'; ?>

<main class="container-fluid app-container py-3 py-lg-4">
    <section class="page-header p-4 mb-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <div>
            <span class="eyebrow">CATÁLOGO DE REPOSIÇÃO</span>
            <h1 class="h3 mt-2 mb-1 fw-bold">Cadastro de itens</h1>
            <p class="text-white-50 mb-0">Cadastre, edite ou inative itens sem apagar o histórico.</p>
        </div>
        <button class="btn btn-warning px-4" id="novoItem"><i class="bi bi-plus-lg me-1"></i>Novo item</button>
    </section>

    <section class="card catalog-card p-3">
        <div class="row g-2 align-items-end mb-3">
            <div class="col-lg-6"><label class="filter-label" for="busca">PESQUISAR</label><div class="input-group"><span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-secondary"></i></span><input class="form-control filter-control border-start-0 ps-0" id="busca" placeholder="Nome, categoria ou unidade" autocomplete="off"></div></div>
            <div class="col-6 col-lg-2"><label class="filter-label" for="filtroOrigem">ORIGEM</label><select class="form-select filter-control" id="filtroOrigem"><option value="">Todas</option><option>Fábrica</option><option>Loja</option></select></div>
            <div class="col-6 col-lg-2"><label class="filter-label" for="filtroStatus">STATUS</label><select class="form-select filter-control" id="filtroStatus"><option value="">Todos</option><option value="1">Ativos</option><option value="0">Inativos</option></select></div>
            <div class="col-lg-2 d-flex justify-content-lg-end pb-2"><div class="result-count"><strong id="contador"><?= count($itens) ?></strong> itens encontrados</div></div>
        </div>

        <div class="table-responsive table-wrap">
            <table class="table table-hover" id="tabelaItens">
                <thead class="table-dark"><tr><th>Item</th><th>Categoria</th><th>Unidade</th><th>Origem</th><th>Status</th><th class="text-end">Ação</th></tr></thead>
                <tbody>
                <?php foreach ($itens as $item): ?>
                    <tr data-busca="<?= htmlspecialchars(mb_strtolower($item['nome'].' '.$item['categoria'].' '.$item['unidade'])) ?>" data-origem="<?= htmlspecialchars($item['origem']) ?>" data-status="<?= (int)$item['ativo'] ?>">
                        <td class="fw-semibold"><?= htmlspecialchars($item['nome']) ?></td>
                        <td><?= htmlspecialchars($item['categoria']) ?></td>
                        <td><?= htmlspecialchars($item['unidade']) ?></td>
                        <td><span class="badge text-bg-<?= $item['origem'] === 'Fábrica' ? 'warning' : 'dark' ?>"><?= htmlspecialchars($item['origem']) ?></span></td>
                        <td><span class="status-dot <?= $item['ativo'] ? 'status-active' : 'status-inactive' ?>"></span><?= $item['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                        <td class="text-end"><button class="btn btn-sm btn-outline-dark editar-item"
                            data-id="<?= (int)$item['ID_ITM'] ?>" data-nome="<?= htmlspecialchars($item['nome']) ?>"
                            data-unidade="<?= htmlspecialchars($item['unidade']) ?>" data-origem="<?= htmlspecialchars($item['origem']) ?>"
                            data-categoria="<?= (int)$item['ID_CAT'] ?>" data-ativo="<?= (int)$item['ativo'] ?>"><i class="bi bi-pencil"></i> Editar</button></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="empty-state d-none" id="vazio"><i class="bi bi-search fs-1"></i><p class="mt-2 mb-0">Nenhum item encontrado.</p></div>
        </div>
    </section>
</main>

<div class="modal fade" id="modalItem" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <form id="formItem" action="itens_api.php" method="post">
            <div class="modal-header"><div><small class="text-warning fw-bold">CATÁLOGO</small><h2 class="modal-title h5" id="tituloModal">Novo item</h2></div><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_itens']) ?>">
                <input type="hidden" name="acao" id="acao" value="criar"><input type="hidden" name="id_itm" id="id_itm">
                <div class="mb-3"><label class="form-label" for="nome">Nome do item</label><input class="form-control" name="nome" id="nome" maxlength="100" required></div>
                <div class="row g-3">
                    <div class="col-sm-6"><label class="form-label" for="origem">1. Destino do item</label><select class="form-select" name="origem" id="origem" required><option value="">Escolha Fábrica ou Loja</option><option>Fábrica</option><option>Loja</option></select></div>
                    <div class="col-sm-6"><label class="form-label" for="id_cat">2. Categoria</label><select class="form-select" name="id_cat" id="id_cat" required disabled><option value="">Escolha primeiro o destino</option></select></div>
                    <div class="col-12"><div class="form-text mt-0" id="ajudaCategoria">As categorias serão exibidas depois que você escolher Fábrica ou Loja.</div></div>
                    <div class="col-sm-7">
                        <label class="form-label" for="unidade">3. Unidade de medida</label>
                        <select class="form-select" name="unidade" id="unidade" required>
                            <option value="">Selecione uma unidade</option>
                            <?php foreach ($unidadesPermitidas as $valorUnidade => $configuracaoUnidade): ?>
                                <option value="<?= htmlspecialchars($valorUnidade) ?>"><?= htmlspecialchars($configuracaoUnidade['rotulo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Use uma opção padronizada; somente kg e litro aceitam frações.</div>
                    </div>
                    <div class="col-sm-5 d-flex align-items-end"><div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="ativo" value="1" id="ativo" checked><label class="form-check-label" for="ativo">Item ativo</label></div></div>
                </div>
                <div class="alert alert-secondary small mt-3 mb-0"><i class="bi bi-info-circle"></i> Itens usados em pedidos não são excluídos. Desative-os para impedir novas solicitações.</div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-warning" type="submit" id="salvarItem">Salvar item</button></div>
        </form>
    </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
const categoriasPorOrigem = <?= json_encode($categoriasPorOrigem, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>
<script src="../../assets/js/admin/itens.js"></script>
</body></html>
