document.getElementById("meuFormulario").addEventListener("submit", function(e) {
  e.preventDefault();

  const form = e.target;
  const dados = new FormData(form);
  const url = form.action || window.location.href; // se action="" usa a própria página

  fetch(url, { method: form.method || 'POST', body: dados })
    .then(async (response) => {
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || !data.ok) {
        throw new Error(data?.message || 'Algo deu errado.');
      }
      return data;
    })
    .then(({ message }) => {
      Swal.fire({
        icon: 'success',
        title: 'Sucesso!',
        text: message,
        timer: 2000,
        showConfirmButton: false
      }).then(() => {
        // opcional: recarregar
        // location.reload();
      });
      form.reset();
    })
    .catch((err) => {
      Swal.fire({
        icon: 'error',
        title: 'Oops...',
        text: err.message
      });
    });
});
