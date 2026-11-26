        function verificarCampos() {
            const nome = document.getElementById('motoboy').value.trim();
            const taxas = document.getElementById('taxas').value;
            const diariaP = document.getElementById('diariaPaga');
            const diariaNP = document.getElementById('diariaNaoPaga');
            const data = document.getElementById('dataa').value;
            const botao = document.getElementById('botao');

            const diariaSelecionada = diariaP.checked || diariaNP.checked;

            const camposPreenchidos = nome && taxas && diariaSelecionada && data;

            if (camposPreenchidos) {
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

        function atualizarGasolina() {
            const diariaNaoPaga = document.getElementById('diariaNaoPaga').checked;
            const divgas = document.getElementById('divgas');

            if (diariaNaoPaga) {
              divgas.classList.remove('d-none');
              divgas.classList.add('d-block');
              divgas.classList.add('anime');
            } else {
               divgas.classList.remove('d-block');
              divgas.classList.add('d-none');
            }
            verificarCampos();
        }

        document.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', verificarCampos);
        });
        document.getElementById('dataa').addEventListener('change', verificarCampos);
