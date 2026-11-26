    document.querySelector("#formzap").addEventListener("submit", function(e) {
      e.preventDefault();

      const dados = new FormData(this);

      fetch(this.action, {
          method: "POST",
          body: dados
        })
        .then(async response => {
          const data = await response.json().catch(() => null);
          if (!response.ok || !data || !data.ok) {
            throw new Error(data?.message || "Erro ao enviar transmissão.");
          }
          return data;
        })
        .then(({
          message
        }) => {
          Swal.fire({
            icon: "success",
            title: "Sucesso!",
            text: message,
            timer: 2000,
            showConfirmButton: false
          }).then(() => location.reload());
        })
        .catch(err => {
          Swal.fire({
            icon: "error",
            title: "Oops...",
            text: err.message
          });
        });
    });
