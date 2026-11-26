document.addEventListener('DOMContentLoaded', () => {
    const datainicio = document.getElementById('datainicio');
    const datafim = document.getElementById('datafim');
    const botao = document.getElementById('botao');

    function validarCampos() {
        if (datainicio.value && datafim.value) {
            botao.classList.remove('disabled');
            botao.classList.add('btn-submit');
            botao.disabled = false;
        } else {
            botao.classList.add('disabled');
            botao.disabled = true;
        }
    }

    datainicio.addEventListener('input', validarCampos);
    datafim.addEventListener('input', validarCampos);
    validarCampos();
});
