document.getElementById('pesquisaNome').addEventListener('input', function () {
  const termo = this.value.toLowerCase();
  document.querySelectorAll('table tbody tr').forEach(tr => {
    const nome = tr.querySelector('td').innerText.toLowerCase(); // primeira coluna (Nome)
    tr.style.display = nome.includes(termo) ? '' : 'none';
  });
});
