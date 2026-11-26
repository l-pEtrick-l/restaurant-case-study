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

            fetch('../admin/pagamento_api_delete.php', {
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
