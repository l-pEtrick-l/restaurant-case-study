document.querySelectorAll('.toggle-edicao').forEach(button => {
  button.addEventListener('click', function () {
    const tr = this.closest('tr');
    tr.querySelectorAll('.form-editar').forEach(el => {
      el.classList.toggle('d-none');
    });
  });
});

document.querySelectorAll('.toggle-edicao').forEach(btn => {
  btn.addEventListener('click', () => {
    const row = btn.closest('.linha-edicao');
    row.classList.toggle('editando');
    const tabela = document.querySelector('.tabela-edicao');
    const algumaEditando = document.querySelector('.linha-edicao.editando');
    if (algumaEditando) {
      tabela.classList.remove('sem-edicao');
    } else {
      tabela.classList.add('sem-edicao');
    }
  });
});
