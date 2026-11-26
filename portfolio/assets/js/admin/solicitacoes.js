const linhas = [...document.querySelectorAll('.request-row')];
const cartoes = [...document.querySelectorAll('[data-request-card]')];
const modalPedido = new bootstrap.Modal('#modalPedido');
const botaoFinalizar = document.getElementById('finalizarPedido');
const porPagina = 12;
let paginaAtual = 1;
let pedidoAtualId = null;

const normalizar = valor => valor.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
const escapar = valor => { const elemento = document.createElement('div'); elemento.textContent = valor ?? ''; return elemento.innerHTML; };

function dentroDoPeriodo(data, periodo) {
    if (!periodo) return true;
    const dataPedido = new Date(`${data}T00:00:00`);
    const agora = new Date();
    const hoje = new Date(agora.getFullYear(), agora.getMonth(), agora.getDate());
    if (periodo === 'today') return dataPedido.getTime() === hoje.getTime();
    const limite = new Date(hoje);
    limite.setDate(limite.getDate() - (Number(periodo) - 1));
    return dataPedido >= limite && dataPedido <= hoje;
}

function filtrarPedidos() {
    const termo = normalizar(document.getElementById('buscaPedido').value);
    const condicao = document.getElementById('filtroCondicao').value;
    const periodo = document.getElementById('filtroPeriodo').value;
    const encontrados = linhas.filter(linha => normalizar(linha.dataset.search).includes(termo) && (!condicao || linha.dataset.status === condicao) && dentroDoPeriodo(linha.dataset.date, periodo));
    const totalPaginas = Math.max(1, Math.ceil(encontrados.length / porPagina));
    paginaAtual = Math.min(paginaAtual, totalPaginas);
    const inicio = (paginaAtual - 1) * porPagina;
    const idsVisiveis = new Set(encontrados.slice(inicio, inicio + porPagina).map(linha => linha.dataset.requestId));

    linhas.forEach(linha => linha.classList.toggle('d-none', !idsVisiveis.has(linha.dataset.requestId)));
    cartoes.forEach(cartao => cartao.classList.toggle('d-none', !idsVisiveis.has(cartao.dataset.requestCard)));
    document.getElementById('semPedidos').style.display = encontrados.length ? 'none' : 'block';
    document.getElementById('resumoPagina').textContent = encontrados.length
        ? `${encontrados.length} ${encontrados.length === 1 ? 'solicitação' : 'solicitações'} · página ${paginaAtual} de ${totalPaginas}`
        : '0 solicitações';
    document.getElementById('paginaAnterior').disabled = paginaAtual <= 1;
    document.getElementById('proximaPagina').disabled = paginaAtual >= totalPaginas;
}

function classeStatus(condicao) {
    const valor = normalizar(condicao);
    if (['concluido', 'atendido', 'finalizado'].includes(valor)) return 'status-success';
    if (['cancelado', 'recusado'].includes(valor)) return 'status-danger';
    if (['em andamento', 'separando', 'processando'].includes(valor)) return 'status-info';
    return 'status-warning';
}

function atualizarBotaoFinalizacao(condicao) {
    const finalizado = normalizar(condicao) === 'finalizado';
    botaoFinalizar.disabled = finalizado;
    botaoFinalizar.classList.toggle('btn-success', !finalizado);
    botaoFinalizar.classList.toggle('btn-outline-success', finalizado);
    botaoFinalizar.innerHTML = finalizado
        ? '<i class="bi bi-check2-all me-1"></i>Pedido finalizado'
        : '<i class="bi bi-check2-circle me-1"></i>Marcar como finalizado';
}

function atualizarCondicaoNaLista(id, condicao) {
    const linha = linhas.find(item => item.dataset.requestId === String(id));
    const cartao = cartoes.find(item => item.dataset.requestCard === String(id));
    [linha, cartao].filter(Boolean).forEach(elemento => {
        elemento.dataset.status = condicao;
        const status = elemento.querySelector('.status-pill');
        if (status) {
            status.className = `status-pill ${classeStatus(condicao)}`;
            status.textContent = condicao;
        }
    });
}

function abrirPedido(id) {
    const pedido = pedidos[id];
    if (!pedido) return;
    pedidoAtualId = String(id);
    document.getElementById('tituloPedido').textContent = `Solicitação #${pedido.id}`;
    document.getElementById('metaPedido').innerHTML = `
        <div class="detail-meta-item"><span>SOLICITANTE</span><strong>${escapar(pedido.solicitante)}</strong></div>
        <div class="detail-meta-item"><span>DATA</span><strong>${escapar(pedido.data)}</strong></div>
        <div class="detail-meta-item"><span>CONDIÇÃO</span><strong><span class="status-pill ${classeStatus(pedido.condicao)}">${escapar(pedido.condicao)}</span></strong></div>`;
    document.getElementById('observacaoPedidoDetalhe').innerHTML = pedido.observacao
        ? `<div class="detail-note"><strong>Observação geral:</strong> ${escapar(pedido.observacao)}</div>`
        : '<div class="text-secondary small">Este pedido não possui observação geral.</div>';
    document.getElementById('totalItensPedido').textContent = `${pedido.itens.length} ${pedido.itens.length === 1 ? 'item' : 'itens'}`;
    document.getElementById('itensPedidoDetalhe').innerHTML = pedido.itens.length
        ? pedido.itens.map(item => `<div class="detail-item"><div><div class="detail-item-name">${escapar(item.nome)}</div><div class="detail-item-path">${escapar(item.origem)} · ${escapar(item.categoria)}</div></div><div class="detail-item-quantity">${escapar(item.quantidade)} ${escapar(item.unidade)}</div>${item.observacao ? `<div class="detail-item-note"><i class="bi bi-chat-left-text me-1"></i>${escapar(item.observacao)}</div>` : ''}</div>`).join('')
        : '<div class="text-center text-secondary py-4">Nenhum item vinculado a esta solicitação.</div>';
    atualizarBotaoFinalizacao(pedido.condicao);
    modalPedido.show();
}

async function finalizarPedido() {
    const pedido = pedidos[pedidoAtualId];
    if (!pedido || normalizar(pedido.condicao) === 'finalizado') return;

    const confirmacao = await Swal.fire({
        icon: 'question',
        title: `Finalizar solicitação #${pedido.id}?`,
        text: 'Ela continuará disponível no histórico com a condição Finalizado.',
        showCancelButton: true,
        confirmButtonText: 'Sim, finalizar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#198754',
        cancelButtonColor: '#6c757d'
    });
    if (!confirmacao.isConfirmed) return;

    botaoFinalizar.disabled = true;
    botaoFinalizar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Finalizando...';

    const dados = new FormData();
    dados.append('csrf_token', csrfSolicitacoes);
    dados.append('pedido_id', pedido.id);

    try {
        const resposta = await fetch('solicitacoes_api.php', { method: 'POST', body: dados });
        const resultado = await resposta.json();
        if (!resposta.ok || !resultado.ok) throw new Error(resultado.message || 'Não foi possível finalizar.');

        const condicaoAnterior = pedido.condicao;
        pedido.condicao = resultado.condicao;
        atualizarCondicaoNaLista(pedido.id, pedido.condicao);
        abrirPedido(pedido.id);

        if (normalizar(condicaoAnterior) === 'solicitado') {
            const totalSolicitado = document.getElementById('totalSolicitado');
            totalSolicitado.textContent = Math.max(0, Number(totalSolicitado.textContent) - 1);
        }
        filtrarPedidos();
        await Swal.fire({ icon: 'success', title: 'Pedido finalizado', text: resultado.message, confirmButtonColor: '#198754' });
    } catch (erro) {
        atualizarBotaoFinalizacao(pedido.condicao);
        await Swal.fire({ icon: 'error', title: 'Não foi possível finalizar', text: erro.message });
    }
}

document.querySelectorAll('.ver-pedido').forEach(botao => botao.addEventListener('click', () => abrirPedido(botao.dataset.pedido)));
botaoFinalizar.addEventListener('click', finalizarPedido);
['buscaPedido', 'filtroCondicao', 'filtroPeriodo'].forEach(id => document.getElementById(id).addEventListener(id === 'buscaPedido' ? 'input' : 'change', () => { paginaAtual = 1; filtrarPedidos(); }));
document.getElementById('paginaAnterior').addEventListener('click', () => { if (paginaAtual > 1) { paginaAtual--; filtrarPedidos(); } });
document.getElementById('proximaPagina').addEventListener('click', () => { paginaAtual++; filtrarPedidos(); });
filtrarPedidos();
