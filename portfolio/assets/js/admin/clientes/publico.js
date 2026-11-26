    document.addEventListener('DOMContentLoaded', () => {
      const map = [{
          checkbox: 'ActiveClient',
          input: 'quantidadeativo'
        },
        {
          checkbox: 'InactiveClient',
          input: 'quantidadeinativo'
        },
        {
          checkbox: 'PotentialClient',
          input: 'quantidadepotencial'
        }
      ];

      map.forEach(pair => {
        const check = document.getElementById(pair.checkbox);
        const input = document.getElementById(pair.input);

        // Começa desabilitado se o checkbox não estiver marcado
        input.disabled = !check.checked;

        // Atualiza quando clicar
        check.addEventListener('change', () => {
          input.disabled = !check.checked;
          if (!check.checked) input.value = ''; // limpa o valor se desmarcar
        });
      });
    });
