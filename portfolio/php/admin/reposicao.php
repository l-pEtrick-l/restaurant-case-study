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
$_SESSION['csrf_reposicao'] = bin2hex(random_bytes(32));
$unidadesPermitidas = require __DIR__ . '/unidades_itens.php';

$grupos = [];
$resultado = $mysqli->query(
    'SELECT i.ID_ITM, i.nome, i.unidade, i.origem, c.categoria
       FROM ITENS i
       JOIN CATEGORIA c ON c.ID_CAT = i.ID_CAT
      WHERE i.ativo = 1
      ORDER BY i.origem, c.categoria, i.nome'
);

while ($item = $resultado->fetch_assoc()) {
    $grupos[$item['origem']][$item['categoria']][] = $item;
}

$iconesCategorias = [
    'Produção' => 'bi-gear-wide-connected',
    'Mercado Fábrica' => 'bi-basket2',
    'Limpeza Fábrica' => 'bi-stars',
    'Congelados e Porções' => 'bi-snow2',
    'Pães' => 'bi-bag',
    'Frios, Queijos e Molhos' => 'bi-droplet-half',
    'Sobremesas' => 'bi-cake2',
    'Milk-shake' => 'bi-cup-straw',
    'Sucos e Polpas' => 'bi-cup',
    'Bebidas' => 'bi-bottle',
    'Embalagens e Descartáveis' => 'bi-box2',
    'Limpeza Loja' => 'bi-stars',
    'Mercado Loja' => 'bi-cart3',
];

function chaveGrupo(string $origem, string $categoria): string
{
    return substr(hash('sha256', $origem . '|' . $categoria), 0, 12);
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>180 Foodhouse - Reposição</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../../img/logofood.png">
    <link rel="stylesheet" href="../../assets/css/admin/reposicao.css">
</head>
<body>
<?php include __DIR__ . '/navbar_admin.php'; ?>

<main class="container-fluid app-container py-3 py-lg-4">
    <header class="app-intro">
        <div>
            <div class="eyebrow">NOVA SOLICITAÇÃO</div>
            <h1 class="page-title">Monte a reposição</h1>
            <p class="page-subtitle">Selecione uma categoria, informe as quantidades e revise o carrinho.</p>
        </div>
        <label class="search-box" for="buscaItem">
            <i class="bi bi-search"></i>
            <input type="search" class="form-control" id="buscaItem" placeholder="Buscar item nesta origem" autocomplete="off">
        </label>
    </header>

    <?php if (!$grupos): ?>
        <div class="alert alert-warning border-0 shadow-sm">Nenhum item ativo foi cadastrado. <a href="itens.php" class="alert-link">Cadastre os itens primeiro.</a></div>
    <?php else: ?>
        <div class="origin-switcher" role="tablist" aria-label="Origem dos itens">
            <?php foreach ($grupos as $indiceOrigem => $categorias): ?>
                <button type="button" class="origin-btn<?= $indiceOrigem === array_key_first($grupos) ? ' active' : '' ?>" data-origin="<?= htmlspecialchars($indiceOrigem) ?>" role="tab">
                    <i class="bi <?= $indiceOrigem === 'Fábrica' ? 'bi-gear-wide-connected' : 'bi-shop' ?> me-1"></i><?= htmlspecialchars($indiceOrigem) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <div class="mobile-categories" id="mobileCategories" aria-label="Categorias"></div>

        <div class="catalog-layout">
            <aside class="panel category-panel" aria-label="Lista de categorias">
                <div class="category-caption">CATEGORIAS</div>
                <?php foreach ($grupos as $origem => $categorias): ?>
                    <div class="category-list" data-category-origin="<?= htmlspecialchars($origem) ?>">
                        <?php foreach ($categorias as $categoria => $lista): $chave = chaveGrupo($origem, $categoria); ?>
                            <button type="button" class="category-btn" data-group="<?= $chave ?>" data-category="<?= htmlspecialchars($categoria) ?>">
                                <span class="category-icon"><i class="bi <?= $iconesCategorias[$categoria] ?? 'bi-grid' ?>"></i></span>
                                <span class="category-name"><?= htmlspecialchars($categoria) ?></span>
                                <span class="category-count"><?= count($lista) ?></span>
                            </button>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </aside>

            <section class="panel catalog-panel" aria-live="polite">
                <?php foreach ($grupos as $origem => $categorias): ?>
                    <?php foreach ($categorias as $categoria => $lista): $chave = chaveGrupo($origem, $categoria); ?>
                        <div class="category-section" data-section="<?= $chave ?>" data-origin="<?= htmlspecialchars($origem) ?>" data-category-name="<?= htmlspecialchars($categoria) ?>">
                            <div class="catalog-header">
                                <div><h2><?= htmlspecialchars($categoria) ?></h2><p><?= htmlspecialchars($origem) ?> · <?= count($lista) ?> <?= count($lista) === 1 ? 'item disponível' : 'itens disponíveis' ?></p></div>
                                <span class="category-icon"><i class="bi <?= $iconesCategorias[$categoria] ?? 'bi-grid' ?>"></i></span>
                            </div>
                            <div class="item-grid">
                                <?php foreach ($lista as $item):
                                    $unidadeNormalizada = mb_strtolower(trim($item['unidade']));
                                    $fracionado = (bool) ($unidadesPermitidas[$unidadeNormalizada]['permite_fracao'] ?? false);
                                    $busca = mb_strtolower($item['nome'] . ' ' . $item['unidade'] . ' ' . $categoria);
                                ?>
                                    <article class="item-card" data-id="<?= (int)$item['ID_ITM'] ?>" data-nome="<?= htmlspecialchars($item['nome']) ?>" data-unidade="<?= htmlspecialchars($item['unidade']) ?>" data-fracionado="<?= $fracionado ? '1' : '0' ?>" data-search="<?= htmlspecialchars($busca) ?>" data-group="<?= $chave ?>">
                                        <div class="item-top">
                                            <h3 class="item-name"><?= htmlspecialchars($item['nome']) ?></h3>
                                            <span class="unit-pill"><?= htmlspecialchars($item['unidade']) ?></span>
                                        </div>
                                        <div class="item-hint"><?= $fracionado ? 'Aceita quantidade fracionada' : 'Quantidade inteira' ?></div>
                                        <div class="item-actions">
                                            <button type="button" class="add-btn"><i class="bi bi-plus-lg me-1"></i>Adicionar</button>
                                            <div class="stepper" aria-label="Quantidade de <?= htmlspecialchars($item['nome']) ?>">
                                                <button type="button" class="step-btn menos" aria-label="Diminuir quantidade">−</button>
                                                <input type="number" class="qtd" value="0" min="0" max="999999.999" step="<?= $fracionado ? '0.1' : '1' ?>" inputmode="decimal" aria-label="Quantidade">
                                                <button type="button" class="step-btn mais" aria-label="Aumentar quantidade">+</button>
                                            </div>
                                            <button type="button" class="note-toggle"><i class="bi bi-chat-left-text"></i><span>Observação</span></button>
                                        </div>
                                        <input class="form-control obs-item" maxlength="100" placeholder="Ex.: enviar primeiro, verificar validade..." aria-label="Observação de <?= htmlspecialchars($item['nome']) ?>">
                                    </article>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <div class="empty-search" id="emptySearch"><i class="bi bi-search"></i><strong>Nenhum item encontrado</strong><div class="small mt-1">Tente outro nome ou troque a origem.</div></div>
            </section>

            <aside class="cart-column">
                <div class="panel cart-panel">
                    <div class="cart-head"><h2 class="cart-title"><i class="bi bi-bag-check"></i>Seu pedido</h2><span class="cart-count desktop-cart-count">0 itens</span></div>
                    <div class="cart-body"><div class="summary-list desktop-summary"></div></div>
                    <div class="cart-footer">
                        <label for="observacaoDesktop" class="form-label small fw-semibold">Observação geral</label>
                        <textarea class="form-control order-note" id="observacaoDesktop" maxlength="100" rows="2" placeholder="Ex.: prioridade para sexta-feira"></textarea>
                        <button class="send-btn send-order" type="button" disabled><i class="bi bi-send me-1"></i>Enviar solicitação</button>
                    </div>
                </div>
            </aside>
        </div>
    <?php endif; ?>
</main>

<?php if ($grupos): ?>
<div class="mobile-cart-bar">
    <button class="mobile-cart-button" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileCart" aria-controls="mobileCart">
        <span><i class="bi bi-bag-check me-2"></i>Revisar pedido</span><span class="mobile-cart-total">0</span>
    </button>
</div>
<div class="offcanvas offcanvas-bottom" tabindex="-1" id="mobileCart" aria-labelledby="mobileCartTitle">
    <div class="offcanvas-header border-bottom"><h2 class="offcanvas-title h5 fw-bold" id="mobileCartTitle">Seu pedido</h2><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Fechar"></button></div>
    <div class="offcanvas-body d-flex flex-column">
        <div class="summary-list mobile-summary flex-grow-1"></div>
        <div class="pt-3 border-top">
            <label for="observacaoMobile" class="form-label small fw-semibold">Observação geral</label>
            <textarea class="form-control order-note" id="observacaoMobile" maxlength="100" rows="2" placeholder="Ex.: prioridade para sexta-feira"></textarea>
            <button class="send-btn send-order" type="button" disabled><i class="bi bi-send me-1"></i>Enviar solicitação</button>
        </div>
    </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<?php if ($grupos): ?>
<script>
const csrf = <?= json_encode($_SESSION['csrf_reposicao']) ?>;
const state = { origin: <?= json_encode((string) array_key_first($grupos)) ?>, group: null, search: '' };
</script>
<script src="../../assets/js/admin/reposicao.js"></script>
<?php endif; ?>
</body>
</html>
