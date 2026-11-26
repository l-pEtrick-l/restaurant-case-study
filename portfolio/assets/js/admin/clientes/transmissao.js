document.getElementById('btnTransmitir').addEventListener('click', async () => {
  const itemAtivo = document.querySelector('#listaCampanhas li.active');
  if (!itemAtivo) {
    Swal.fire({
      icon: 'warning',
      title: 'Selecione uma campanha!',
      text: 'Clique em uma campanha antes de enviar.'
    });
    return;
  }

  const nomeCampanha = itemAtivo.dataset.nome;
  const msg = itemAtivo.dataset.msg;
  const img = itemAtivo.dataset.img;

  Swal.fire({
    icon: 'question',
    title: 'Confirmar envio?',
    text: `Deseja enviar a campanha "${nomeCampanha}"?`,
    showCancelButton: true,
    confirmButtonText: 'Sim, enviar',
    cancelButtonText: 'Cancelar'
  }).then(async (result) => {
    if (result.isConfirmed) {
      try {
        console.log('JSON enviado:', { campanha: nomeCampanha, mensagem: msg, imagem: img });
        const response = await fetch('process_transmissao.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ campanha: nomeCampanha, mensagem: msg, imagem: img })
        });

        const data = await response.json().catch(() => null);
        console.log('Resposta proxy:', data); // <--- só debug

        if (response.ok && data?.ok) {
          Swal.fire({
            icon: 'success',
            title: 'Transmissão iniciada!',
            text: data.message
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Erro!',
            text: data?.message || 'Não foi possível iniciar a transmissão.'
          });
        }
      } catch (err) {
        console.error('Erro fetch:', err);
        Swal.fire({
          icon: 'error',
          title: 'Erro de rede!',
          text: err.message || 'Falha na conexão com o servidor.'
        });
      }
    }
  });
});
