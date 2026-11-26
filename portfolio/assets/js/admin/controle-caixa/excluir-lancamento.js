document.querySelectorAll('.btn-excluir').forEach(btn => {
    btn.addEventListener('click', () => {
        const tr = btn.closest('tr'); // não precisa mais da classe 'linha-edicao'
        const id = tr.dataset.id;

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
            fd.append('id_temp', id);

            fetch('../admin/controle-caixa_api_delete.php', {
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
