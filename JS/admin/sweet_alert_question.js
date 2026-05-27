const btnn = document.getElementById('btn-smt');

btnn.addEventListener('click', () => {

 
        Swal.fire({
            title: "Tem certeza?",
            text: "Não sera possivel fazer mais alterações após o envio.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Sim, enviar",
            cancelButtonText: "Cancelar"
        }).then(result => {
            if (!result.isConfirmed) return;

            fetch('../admin/process_integration_temp_caixa.php', {
                method: 'POST',
            })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) throw new Error(res.message);
                Swal.fire("Salvo com sucesso!", res.message, "success")
                    .then(() => location.reload());
            })
            .catch(err => {
                Swal.fire("Erro", err.message, "error");
            });
        });
    });
