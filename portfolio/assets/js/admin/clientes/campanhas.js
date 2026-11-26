    document.addEventListener("DOMContentLoaded", () => {
      const itens = document.querySelectorAll("#listaCampanhas li");
      const preview = document.getElementById("previewMessageView");
      const img = document.getElementById("previewImageView");
      const txt = document.getElementById("previewTextView");

      itens.forEach(item => {
        item.addEventListener("click", () => {
          // Marca o item selecionado
          itens.forEach(i => i.classList.remove("active"));
          item.classList.add("active");

          // Atualiza preview
          const nome = item.dataset.nome;
          const msg = item.dataset.msg;
          const imgPath = item.dataset.img;

          txt.textContent = msg;
          if (imgPath && imgPath !== "NULL") {
            img.src = "../../" + imgPath;
            img.style.display = "block";
          } else {
            img.style.display = "none";
          }

          preview.style.display = "block";
        });
      });
    });
