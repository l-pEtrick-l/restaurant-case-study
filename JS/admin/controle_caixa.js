    document.addEventListener("DOMContentLoaded", function() {
      const botao = document.getElementById("botao");
      const tipo = document.getElementById("tipo");
      const valor = document.getElementById("valor");
      const nome = document.getElementById("nome");

      tipo.addEventListener("change", function() {

        if (tipo.value === "gasolina") {
          valor.disabled = true;
          valor.value = 40;
        } else {
          valor.disabled = false;
          valor.value = ""
        }
      });

      function validarCampos() {
        if ((tipo.value !== "gasolina" && nome.value && valor.value) || (tipo.value === "gasolina" && nome.value)) {
          botao.classList.remove('disabled');
          botao.classList.add('btn-primary');
          botao.classList.remove('btn-secondary');
          botao.disabled = false;
        } else {
          botao.classList.add('disabled');
          botao.classList.remove('btn-primary');
          botao.classList.add('btn-secondary');
          botao.disabled = true;
        }
      }

      // Eventos de input para validar campos dinamicamente
      tipo.addEventListener('change', validarCampos);
      valor.addEventListener('input', validarCampos);
      nome.addEventListener('change', validarCampos);

      // Validação inicial
      validarCampos();

      const form = document.getElementById("formCaixa");

      form.addEventListener("submit", function(e) {
        e.preventDefault();

        const dados = new FormData(form);
        const url = form.action;

        fetch(url, {
            method: "POST",
            body: dados
          })
          .then(async response => {
            const data = await response.json().catch(() => null);
            if (!response.ok || !data || !data.ok) {
              throw new Error(data?.message || "Algo deu errado.");
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
            }).then(() => {
              location.reload();
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
    });