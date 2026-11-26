            document.addEventListener("DOMContentLoaded", function() {
              const btnPrintZap = document.getElementById("btnPrintZap");
              const tabelas = document.querySelector(".fundotab");

              // Só mostra o botão se tiver resultado (ou seja, se as tabelas forem renderizadas)
              if (temFechamento) {
                btnPrintZap.classList.remove("d-none");
              }

              btnPrintZap.addEventListener("click", async () => {
                btnPrintZap.disabled = true;
                btnPrintZap.textContent = "Gerando imagem...";

                // Gera o print das tabelas
                const canvas = await html2canvas(tabelas, {
                  scale: 2
                });
                const imgData = canvas.toDataURL("image/png");

                // Cria o link para download da imagem
                const link = document.createElement("a");
                link.download = "fechamento.png";
                link.href = imgData;
                link.click();


                btnPrintZap.disabled = false;
                btnPrintZap.innerHTML = '<i class="bi bi-card-image"></i> Baixar fechamento diario novamente';
              });
            });
