    const imgInput = document.getElementById('imgzap');
    const previewImg = document.getElementById('previewImage');
    const textInput = document.getElementById('escopomsg');
    const previewText = document.getElementById('previewText');

    // Atualiza imagem em tempo real
    imgInput.addEventListener('change', function(event) {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          previewImg.src = e.target.result;
          previewImg.style.display = 'block';
        };
        reader.readAsDataURL(file);
      } else {
        previewImg.style.display = 'none';
      }
    });

    // Atualiza texto em tempo real
    textInput.addEventListener('input', function() {
      previewText.textContent = textInput.value;
    });


    // Atualiza o horário da mensagem com o horário atual
    function updateTime() {
      const now = new Date();
      const hours = now.getHours().toString().padStart(2, '0');
      const minutes = now.getMinutes().toString().padStart(2, '0');
      document.getElementById('msgTime').textContent = `${hours}:${minutes}`;
    }

    // Atualiza o horário a cada 30 segundos
    updateTime();
    setInterval(updateTime, 30000);
