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

            fetch("../admin/pagamento_api_batch.php", {
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
