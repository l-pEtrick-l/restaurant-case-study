const modal = new bootstrap.Modal('#modalItem');
const form = document.getElementById('formItem');
const origemInput = document.getElementById('origem');
const categoriaInput = document.getElementById('id_cat');
const ajudaCategoria = document.getElementById('ajudaCategoria');
const normalizar = texto => texto.normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();

function carregarCategorias(categoriaSelecionada = '') {
    const origem = origemInput.value;
    const categorias = categoriasPorOrigem[origem] || [];
    categoriaInput.innerHTML = `<option value="">${origem ? 'Selecione a categoria' : 'Escolha primeiro o destino'}</option>`;
    categorias.forEach(categoria => {
        const option = document.createElement('option');
        option.value = categoria.ID_CAT;
        option.textContent = categoria.categoria;
        categoriaInput.appendChild(option);
    });
    categoriaInput.disabled = !origem || categorias.length === 0;
    if (categoriaSelecionada && categorias.some(categoria => String(categoria.ID_CAT) === String(categoriaSelecionada))) {
        categoriaInput.value = String(categoriaSelecionada);
    }
    ajudaCategoria.textContent = origem
        ? `${categorias.length} ${categorias.length === 1 ? 'categoria disponível' : 'categorias disponíveis'} para ${origem}.`
        : 'As categorias serão exibidas depois que você escolher Fábrica ou Loja.';
}

origemInput.addEventListener('change', () => carregarCategorias());

document.getElementById('novoItem').addEventListener('click', () => {
    form.reset(); document.getElementById('acao').value='criar'; document.getElementById('id_itm').value='';
    document.getElementById('ativo').checked=true; document.getElementById('tituloModal').textContent='Novo item'; carregarCategorias(); modal.show();
});
document.querySelectorAll('.editar-item').forEach(btn => btn.addEventListener('click', () => {
    form.reset(); document.getElementById('acao').value='editar'; document.getElementById('id_itm').value=btn.dataset.id;
    document.getElementById('nome').value=btn.dataset.nome; document.getElementById('unidade').value=btn.dataset.unidade;
    origemInput.value=btn.dataset.origem; carregarCategorias(btn.dataset.categoria);
    document.getElementById('ativo').checked=btn.dataset.ativo==='1'; document.getElementById('tituloModal').textContent='Editar item'; modal.show();
}));

function filtrar() {
    const termo=normalizar(document.getElementById('busca').value), origem=document.getElementById('filtroOrigem').value, status=document.getElementById('filtroStatus').value;
    let visiveis=0;
    document.querySelectorAll('#tabelaItens tbody tr').forEach(tr => {
        const exibir=normalizar(tr.dataset.busca).includes(termo) && (!origem || tr.dataset.origem===origem) && (status==='' || tr.dataset.status===status);
        tr.classList.toggle('d-none',!exibir); if(exibir) visiveis++;
    });
    document.getElementById('contador').textContent=visiveis; document.getElementById('vazio').classList.toggle('d-none',visiveis!==0);
}
['busca','filtroOrigem','filtroStatus'].forEach(id => document.getElementById(id).addEventListener(id==='busca'?'input':'change',filtrar));

form.addEventListener('submit', async e => {
    e.preventDefault(); const botao=document.getElementById('salvarItem'); botao.disabled=true;
    try { const resposta=await fetch(form.action,{method:'POST',body:new FormData(form)}); const dados=await resposta.json(); if(!resposta.ok||!dados.ok) throw new Error(dados.message||'Não foi possível salvar.');
        await Swal.fire({icon:'success',title:'Pronto!',text:dados.message,confirmButtonColor:'#ffc107'}); location.reload();
    } catch(erro) { Swal.fire({icon:'error',title:'Não foi possível salvar',text:erro.message}); } finally { botao.disabled=false; }
});
