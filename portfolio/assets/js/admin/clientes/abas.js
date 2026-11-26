    const tabs = document.querySelectorAll('.tab');
    const sections = document.querySelectorAll('.content-section');

    tabs.forEach(tab => {
      tab.addEventListener('click', () => {
        // remove "active" de todos
        tabs.forEach(t => t.classList.remove('active'));
        sections.forEach(sec => sec.classList.remove('active'));

        // adiciona no selecionado
        tab.classList.add('active');
        const target = document.getElementById(tab.dataset.target);
        target.classList.add('active');
      });
    });
