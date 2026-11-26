    const emaill = document.getElementById('emaill');
    const senhaa = document.getElementById('senhaa');
    const botaoo = document.getElementById('botaoo');

    function validarCampos() {
        if (emaill.value && senhaa.value) {
            botaoo.classList.remove('disabled');
            botaoo.classList.add('btn-primary');
            botaoo.classList.remove('btn-secondary');
            botaoo.disabled = false;
        } else {
            botaoo.classList.add('disabled');
            botaoo.classList.remove('btn-primary');
            botaoo.classList.add('btn-secondary');
            botaoo.disabled = true;
        }
    }

    emaill.addEventListener('input', validarCampos);
    senhaa.addEventListener('input', validarCampos);
    validarCampos(); // para validar no carregamento inicial



document.querySelector('form').addEventListener('submit', function (e) {
    const email = document.getElementById('emaill').value;
    const emailValido = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);

    if (!emailValido) {
        e.preventDefault(); // impede envio do formulário
        alert("Por favor, insira um e-mail válido.");
    }
});
