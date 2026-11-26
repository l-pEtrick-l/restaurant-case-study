// Função para habilitar/desabilitar botão de Excel de Pedidos
const btnEnviarPedidos = document.querySelector("#btnenviar");
const formPedidos = document.querySelector("#uploadExcelForm");

const camposPedidos = [
  document.querySelector("#nomeexcel"),
  document.querySelector("#qntmesas"),
  document.querySelector("#cxfinal")
];

function validaPedidos() {
  const todosPreenchidos = camposPedidos.every(c => c.value.trim() !== "");
  btnEnviarPedidos.disabled = !todosPreenchidos;
}

camposPedidos.forEach(campo => campo.addEventListener("input", validaPedidos));
validaPedidos(); // desabilita inicial

// Função para habilitar/desabilitar botão de Excel Caixa
const formCaixa = document.querySelector("#uploadExcelFormCaixa");
const btnEnviarCaixa = formCaixa.querySelector("button");
const inputCaixa = formCaixa.querySelector('input[name="excel_file"]');

inputCaixa.addEventListener("input", () => {
  btnEnviarCaixa.disabled = inputCaixa.value.trim() === "";
});

btnEnviarCaixa.disabled = true; // inicia desabilitado
