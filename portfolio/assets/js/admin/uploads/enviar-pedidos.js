    document.querySelector("#uploadExcelForm").addEventListener("submit", function(e) {
  e.preventDefault();
  const dados = new FormData(this);

  fetch(this.action, {
      method: "POST",
      body: dados
  })
  .then(async response => {
      const data = await response.json().catch(() => null);
      if (!response.ok || !data || !data.ok) {
          throw new Error(data?.message || "Erro ao enviar Excel.");
      }
      return data;
  })
  .then(({ message }) => {
      Swal.fire({
          icon: "success",
          title: "Sucesso!",
          text: message,
          timer: 2000,
          showConfirmButton: false
      }).then(() => {
          // Mostra card do Excel Caixa com animação
          const cardCaixa = document.querySelector("#cardCaixa");
          cardCaixa.classList.add("show");
      });
  })
  .catch(err => {
      Swal.fire({
          icon: "error",
          title: "Oops...",
          text: err.message
      });
  });
});
