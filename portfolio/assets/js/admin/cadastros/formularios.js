/* ================================
   1) CONTROLE DOS FORMULÁRIOS
   ================================ */

// Exibir apenas os campos do tipo selecionado
function mostrarFormulario() {
    const tipo = document.getElementById("tipoUsuario").value;

    // IDs dos blocos de campos
    const formularios = ["camposMotoboy", "camposAdmin", "camposContribuidor"];

    // Esconde e desativa todos
    formularios.forEach(id => {
        const div = document.getElementById(id);
        if (div) {
            div.style.display = "none";
            div.querySelectorAll("input").forEach(input => input.disabled = true);
        }
    });

    // Mostra e ativa apenas o selecionado
    const campoVisivel = document.getElementById(
        "campos" + tipo.charAt(0).toUpperCase() + tipo.slice(1)
    );
    if (campoVisivel) {
        campoVisivel.style.display = "grid";
        campoVisivel.querySelectorAll("input").forEach(input => input.disabled = false);
    }
}

// Ao carregar a página
document.addEventListener("DOMContentLoaded", function () {
    // Seleção de inputs
    const cpfInputs = document.querySelectorAll("input[name='CPF']");
    const telefoneInputs = document.querySelectorAll("input[name='telefone']");
    const emailInputs = document.querySelectorAll("input[name='email']");

    /* Máscara de CPF */
    cpfInputs.forEach(input => {
        input.addEventListener("input", function () {
            let valor = input.value.replace(/\D/g, "");
            if (valor.length > 11) valor = valor.slice(0, 11);
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d)/, "$1.$2");
            valor = valor.replace(/(\d{3})(\d{1,2})$/, "$1-$2");
            input.value = valor;
        });
    });

    /* Máscara de telefone */
    telefoneInputs.forEach(input => {
        input.addEventListener("input", function () {
            let valor = input.value.replace(/\D/g, "");
            if (valor.length > 11) valor = valor.slice(0, 11);
            valor = valor.replace(/^(\d{2})(\d)/g, "($1) $2");
            valor = valor.replace(/(\d{5})(\d{1,4})$/, "$1-$2");
            input.value = valor;
        });
    });

    /* Validação de email */
    emailInputs.forEach(input => {
        const small = document.createElement("small");
        input.parentNode.appendChild(small);

        input.addEventListener("input", function () {
            const email = input.value;
            const padrao = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

            if (padrao.test(email)) {
                input.classList.remove("is-invalid");
                input.classList.add("is-valid");
                small.textContent = "Email válido ✔️";
                small.style.color = "green";
            } else {
                input.classList.remove("is-valid");
                input.classList.add("is-invalid");
                small.textContent = "Email inválido ❌";
                small.style.color = "red";
            }
        });
    });

    // Desativa campos no início
    document.querySelectorAll("#camposMotoboy input, #camposAdmin input, #camposContribuidor input")
        .forEach(input => input.disabled = true);
});


/* ================================
   2) VALIDAÇÃO E BOTÃO DE ENVIO
   ================================ */

const botao = document.getElementById('botao');
const tipoUsuarioSelect = document.getElementById("tipoUsuario");

// Verifica se todos os campos do tipo selecionado estão preenchidos
function validarCampos() {
    const tipoSelecionado = tipoUsuarioSelect.value;

    if (!tipoSelecionado) {
        desativarBotao();
        return;
    }

    const campoAtivo = document.getElementById(
        "campos" + tipoSelecionado.charAt(0).toUpperCase() + tipoSelecionado.slice(1)
    );
    const inputs = campoAtivo.querySelectorAll("input:not([type='hidden'])");

    let todosPreenchidos = true;

    inputs.forEach(input => {
        // Para motoboy, CPF não é obrigatório
        if (tipoSelecionado === "motoboy" && input.name === "CPF") {
            return;
        }
        if (!input.value.trim()) {
            todosPreenchidos = false;
        }
    });

    todosPreenchidos ? ativarBotao() : desativarBotao();
}

// Ativa botão
function ativarBotao() {
    botao.classList.remove('disabled', 'btn-secondary');
    botao.classList.add('btn-primary');
    botao.disabled = false;
}

// Desativa botão
function desativarBotao() {
    botao.classList.add('disabled', 'btn-secondary');
    botao.classList.remove('btn-primary');
    botao.disabled = true;
}

// Escuta mudanças no tipo de usuário
tipoUsuarioSelect.addEventListener("change", function () {
    setTimeout(() => {
        const tipoSelecionado = tipoUsuarioSelect.value;
        const campoAtivo = document.getElementById(
            "campos" + tipoSelecionado.charAt(0).toUpperCase() + tipoSelecionado.slice(1)
        );
        const inputs = campoAtivo.querySelectorAll("input:not([type='hidden'])");

        inputs.forEach(input => {
            input.addEventListener("input", validarCampos);
        });

        validarCampos();
    }, 100); // pequeno delay para DOM atualizar
});

// Executa ao iniciar
validarCampos();
