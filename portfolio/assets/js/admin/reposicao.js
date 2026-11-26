const cards = [...document.querySelectorAll('.item-card')];
const sections = [...document.querySelectorAll('.category-section')];
const categoryLists = [...document.querySelectorAll('.category-list')];
const originButtons = [...document.querySelectorAll('.origin-btn')];
const desktopSummary = document.querySelector('.desktop-summary');
const mobileSummary = document.querySelector('.mobile-summary');

const normalizeText = value => value.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
const formatQuantity = value => new Intl.NumberFormat('pt-BR', { maximumFractionDigits: 3 }).format(value);
const escapeHtml = value => { const element = document.createElement('div'); element.textContent = value; return element.innerHTML; };

function selectedItems() {
    return cards.map(card => ({
        id: Number(card.dataset.id),
        name: card.dataset.nome,
        unit: card.dataset.unidade,
        quantity: Number(card.querySelector('.qtd').value) || 0,
        observation: card.querySelector('.obs-item').value.trim(),
        card
    })).filter(item => item.quantity > 0);
}

function firstGroupForOrigin(origin) {
    return document.querySelector(`.category-list[data-category-origin="${CSS.escape(origin)}"] .category-btn`)?.dataset.group || null;
}

function buildMobileCategories() {
    const container = document.getElementById('mobileCategories');
    const list = document.querySelector(`.category-list[data-category-origin="${CSS.escape(state.origin)}"]`);
    container.innerHTML = '';
    if (!list) return;
    list.querySelectorAll('.category-btn').forEach(button => {
        const mobileButton = document.createElement('button');
        mobileButton.type = 'button';
        mobileButton.className = `mobile-category-btn${button.dataset.group === state.group ? ' active' : ''}`;
        mobileButton.dataset.group = button.dataset.group;
        mobileButton.textContent = button.dataset.category;
        mobileButton.addEventListener('click', () => selectGroup(button.dataset.group));
        container.appendChild(mobileButton);
    });
    container.querySelector('.active')?.scrollIntoView({ inline: 'center', block: 'nearest', behavior: 'smooth' });
}

function animateVisibleCards(section) {
    const visibleCards = [...section.querySelectorAll('.item-card:not(.d-none)')];
    visibleCards.forEach((card, index) => {
        card.classList.remove('stagger-in');
        card.style.animationDelay = `${Math.min(index * 35, 245)}ms`;
        requestAnimationFrame(() => card.classList.add('stagger-in'));
    });
}

function renderCatalog() {
    categoryLists.forEach(list => list.classList.toggle('d-none', list.dataset.categoryOrigin !== state.origin));
    originButtons.forEach(button => {
        const active = button.dataset.origin === state.origin;
        button.classList.toggle('active', active);
        button.setAttribute('aria-selected', active ? 'true' : 'false');
    });

    document.querySelectorAll('.category-btn').forEach(button => button.classList.toggle('active', button.dataset.group === state.group));
    const term = normalizeText(state.search);
    let totalMatches = 0;

    sections.forEach(section => {
        const sameOrigin = section.dataset.origin === state.origin;
        const showSection = sameOrigin && (term !== '' || section.dataset.section === state.group);
        let sectionMatches = 0;
        section.querySelectorAll('.item-card').forEach(card => {
            const matches = !term || normalizeText(card.dataset.search).includes(term);
            card.classList.toggle('d-none', !matches);
            if (matches) sectionMatches++;
        });
        const visible = showSection && sectionMatches > 0;
        section.classList.toggle('active', visible);
        if (visible) {
            totalMatches += sectionMatches;
            animateVisibleCards(section);
        }
    });

    document.getElementById('emptySearch').style.display = totalMatches === 0 ? 'block' : 'none';
    buildMobileCategories();
}

function selectGroup(group) {
    state.group = group;
    state.search = '';
    document.getElementById('buscaItem').value = '';
    renderCatalog();
}

function setQuantity(card, requestedValue) {
    const fractional = card.dataset.fracionado === '1';
    let value = Math.max(0, Math.min(999999.999, Number(requestedValue) || 0));
    value = fractional ? Math.round(value * 1000) / 1000 : Math.round(value);
    card.querySelector('.qtd').value = value;
    card.classList.toggle('is-selected', value > 0);
    if (value === 0) card.querySelector('.obs-item').classList.remove('is-open');
    updateCart(true);
}

function summaryMarkup(items) {
    if (!items.length) return '<div class="summary-empty"><i class="bi bi-bag"></i>Seu carrinho está vazio.<br>Adicione os itens necessários.</div>';
    return items.map(item => `
        <div class="summary-item">
            <div><div class="summary-item-name">${escapeHtml(item.name)}</div><div class="summary-item-unit">${escapeHtml(item.unit)}</div><button type="button" class="remove-summary" data-remove-id="${item.id}"><i class="bi bi-x-lg"></i> Remover</button></div>
            <div class="summary-quantity">${formatQuantity(item.quantity)} ${escapeHtml(item.unit)}</div>
        </div>`).join('');
}

function updateCart(animate = false) {
    const items = selectedItems();
    const countLabel = `${items.length} ${items.length === 1 ? 'item' : 'itens'}`;
    document.querySelectorAll('.desktop-cart-count').forEach(element => element.textContent = countLabel);
    document.querySelectorAll('.mobile-cart-total').forEach(element => element.textContent = items.length);
    document.querySelectorAll('.send-order').forEach(button => button.disabled = items.length === 0);
    const markup = summaryMarkup(items);
    desktopSummary.innerHTML = markup;
    mobileSummary.innerHTML = markup;

    document.querySelectorAll('[data-remove-id]').forEach(button => button.addEventListener('click', () => {
        const card = cards.find(itemCard => itemCard.dataset.id === button.dataset.removeId);
        if (card) setQuantity(card, 0);
    }));

    if (animate) {
        document.querySelectorAll('.cart-count, .mobile-cart-button').forEach(element => {
            element.classList.remove('cart-bump');
            requestAnimationFrame(() => element.classList.add('cart-bump'));
        });
    }
}

originButtons.forEach(button => button.addEventListener('click', () => {
    state.origin = button.dataset.origin;
    state.group = firstGroupForOrigin(state.origin);
    state.search = '';
    document.getElementById('buscaItem').value = '';
    renderCatalog();
}));

document.querySelectorAll('.category-btn').forEach(button => button.addEventListener('click', () => selectGroup(button.dataset.group)));

cards.forEach(card => {
    const input = card.querySelector('.qtd');
    const step = card.dataset.fracionado === '1' ? 0.1 : 1;
    card.querySelector('.add-btn').addEventListener('click', () => setQuantity(card, step));
    card.querySelector('.menos').addEventListener('click', () => setQuantity(card, Number(input.value) - step));
    card.querySelector('.mais').addEventListener('click', () => setQuantity(card, Number(input.value) + step));
    input.addEventListener('change', () => setQuantity(card, input.value));
    card.querySelector('.note-toggle').addEventListener('click', () => {
        const observation = card.querySelector('.obs-item');
        observation.classList.toggle('is-open');
        if (observation.classList.contains('is-open')) observation.focus();
    });
});

document.getElementById('buscaItem').addEventListener('input', event => {
    state.search = event.target.value;
    renderCatalog();
});

document.querySelectorAll('.order-note').forEach(note => note.addEventListener('input', event => {
    document.querySelectorAll('.order-note').forEach(other => { if (other !== event.target) other.value = event.target.value; });
}));

async function submitOrder(sourceButton) {
    const items = selectedItems();
    if (!items.length) return;
    const confirmation = await Swal.fire({
        icon: 'question',
        title: 'Enviar solicitação?',
        html: `Você selecionou <strong>${items.length}</strong> ${items.length === 1 ? 'item' : 'itens'}.`,
        showCancelButton: true,
        confirmButtonText: 'Sim, enviar',
        cancelButtonText: 'Revisar',
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d'
    });
    if (!confirmation.isConfirmed) return;

    document.querySelectorAll('.send-order').forEach(button => button.disabled = true);
    sourceButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Enviando...';
    const data = new FormData();
    data.append('csrf_token', csrf);
    data.append('observacao_pedido', document.querySelector('.order-note').value.trim());
    data.append('itens', JSON.stringify(items.map(item => ({ id: item.id, quantidade: item.quantity, observacao: item.observation }))));

    try {
        const response = await fetch('reposicao_api.php', { method: 'POST', body: data });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.message || 'Não foi possível enviar.');
        await Swal.fire({ icon: 'success', title: `Pedido #${result.pedido_id}`, text: result.message, confirmButtonColor: '#ffc107' });
        location.reload();
    } catch (error) {
        await Swal.fire({ icon: 'error', title: 'Pedido não enviado', text: error.message });
        sourceButton.innerHTML = '<i class="bi bi-send me-1"></i>Enviar solicitação';
        updateCart();
    }
}

document.querySelectorAll('.send-order').forEach(button => button.addEventListener('click', () => submitOrder(button)));
state.group = firstGroupForOrigin(state.origin);
renderCatalog();
updateCart();
