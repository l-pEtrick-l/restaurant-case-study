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
        button.addEventListener('click', function () {
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

document.querySelectorAll(".meuFormulario").forEach(form => {
    form.addEventListener("submit", function (e) {
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


document.getElementById('editar-todas').addEventListener('click', () => {
    document.querySelectorAll('.linha-edicao').forEach(tr => {
        tr.querySelectorAll('.valor-visivel').forEach(span => span.classList.add('d-none'));
        tr.querySelectorAll('.form-editar').forEach(input => input.classList.remove('d-none'));
    });

    document.getElementById('editar-todas').classList.add('d-none');
    document.getElementById('salvar-todas').classList.remove('d-none');
});

document.getElementById('salvar-todas').addEventListener('click', () => {
    const linhas = [];

    document.querySelectorAll('.linha-edicao').forEach(tr => {
        linhas.push({
            data_linha: tr.dataset.data,
            motoboy: tr.dataset.motoboy,
            taxas: tr.querySelector('input[name="taxas"]').value,
            saidas: tr.querySelector('input[name="saidas"]').value,
            entradas: tr.querySelector('input[name="entradas"]').value,
            gasolina: tr.querySelector('select[name="gasolina"]').value,
            diaria: tr.querySelector('select[name="diaria"]').value
        });
    });

    fetch("../../PHP/admin/actions/process_pagamento_batch.php", {
        method: "POST",
        headers: {
            "Content-Type": "application/json"
        },
        body: JSON.stringify(linhas)
    })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                Swal.fire({
                    icon: "success",
                    title: "Sucesso!",
                    text: res.message,
                    timer: 2000,
                    showConfirmButton: false
                }).then(() => location.reload());
            } else {
                throw new Error(res.message);
            }
        })
        .catch(err => {
            Swal.fire({
                icon: "error",
                title: "Erro",
                text: err.message
            });
        });
});


document.querySelectorAll('.btn-excluir').forEach(btn => {
    btn.addEventListener('click', () => {

        const tr = btn.closest('.linha-edicao');
        const data = tr.dataset.data;
        const motoboy = tr.dataset.motoboy;

        Swal.fire({
            title: "Tem certeza?",
            text: "Esse lançamento será apagado.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sim, apagar",
            cancelButtonText: "Cancelar"
        }).then(result => {
            if (!result.isConfirmed) return;

            const fd = new FormData();
            fd.append('data_linha', data);
            fd.append('motoboy', motoboy);

            fetch('../../PHP/admin/actions/process_pagamento_delete.php', {
                method: 'POST',
                body: fd
            })
                .then(r => r.json())
                .then(res => {
                    if (!res.ok) throw new Error(res.message);
                    Swal.fire("Apagado!", res.message, "success")
                        .then(() => location.reload());
                })
                .catch(err => {
                    Swal.fire("Erro", err.message, "error");
                });
        });
    });
});