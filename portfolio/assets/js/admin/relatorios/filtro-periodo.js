        document.addEventListener('DOMContentLoaded', () => {

            // Elementos do formulário
            const datainicio = document.getElementById('datainicio');
            const datafim = document.getElementById('datafim');
            const nomeconsulta = document.getElementById('nomeconsulta');
            const botao = document.getElementById('botao');

            // Função para validar campos e habilitar/desabilitar botão
            function validarCampos() {
                if (datainicio.value && datafim.value && nomeconsulta.value) {
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
            datainicio.addEventListener('input', validarCampos);
            datafim.addEventListener('input', validarCampos);
            nomeconsulta.addEventListener('input', validarCampos);

            // Validação inicial
            validarCampos();

            // Toggle edição para linhas da tabela
            document.querySelectorAll('.toggle-edicao').forEach(button => {
                button.addEventListener('click', function() {
                    const tr = this.closest('tr');
                    tr.querySelectorAll('.form-editar').forEach(el => {
                        el.classList.toggle('d-none');
                    });
                });
            });

            document.querySelectorAll('.toggle-edicao').forEach(btn => {
                btn.addEventListener('click', () => {
                    const row = btn.closest('.linha-edicao');
                    row.classList.toggle('editando');

                    const tabela = document.querySelector('.tabela-edicao');
                    const algumaEditando = document.querySelector('.linha-edicao.editando');

                    if (algumaEditando) {
                        tabela.classList.remove('sem-edicao');
                    } else {
                        tabela.classList.add('sem-edicao');
                    }
                });
            });

        });
