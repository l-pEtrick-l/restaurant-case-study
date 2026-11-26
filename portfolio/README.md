# Restaurant Case Study

Estudo de caso de um sistema de operação de hamburgueria. O núcleo demonstrável cobre o catálogo de itens, a reposição de estoque e o acompanhamento de solicitações. Esta edição usa dados fictícios e não se conecta à operação real.

## O que observar no código

- **Catálogo:** filtro por origem e status; categorias e unidades válidas por destino.
- **Reposição:** montagem do pedido em desktop e celular; validação no servidor e gravação dos itens e do pedido em uma transação.
- **Solicitações:** filtros, visualização dos itens do pedido e transição para finalizado.

As páginas PHP renderizam a interface e os dados iniciais; os scripts em `assets/js` tratam as interações, e os endpoints PHP validam os pedidos antes de persistir no MySQL. As folhas CSS estão em `assets/css`, organizadas por área. O frontend usa Bootstrap e não precisa de uma etapa de build. Os relatórios, caixa, taxas e integrações legadas estão no código para leitura, mas não são cobertos pelo esquema de demonstração.

## Rodar a demonstração local

Requisitos: PHP 8.2+ com `mysqli` e `mbstring`, MySQL ou MariaDB e um banco **vazio e descartável** chamado `restaurant_case`. Não use credenciais nem um banco de produção.

Defina `RESTAURANT_DB_USER` e `RESTAURANT_DB_PASSWORD` no ambiente do processo para uma conta local com acesso ao banco. Por padrão a conexão usa `127.0.0.1:3306`; para outra porta local use `RESTAURANT_DB_PORT`. Os nomes das variáveis, sem valores reais, estão em `.env.example` (o PHP lê o ambiente do processo, não carrega `.env` automaticamente).

```sh
php database/criar_demo.php
php -S 127.0.0.1:8000
```

Abra `http://127.0.0.1:8000` e entre com o usuário e a senha temporária impressos pelo primeiro comando. Roteiro: **Itens → Reposição → Solicitações**. O instalador recusa bancos não vazios e não usa dados da operação.

O núcleo da demonstração não exige `vendor/`. Para inspecionar localmente módulos que usam bibliotecas de planilha ou e-mail, as dependências são instaladas com `composer install`. E-mail e WhatsApp ficam inativos sem configuração explícita; não configure essas integrações para testar a demo. Esta edição é para execução local e não inclui configuração de deploy.

## Verificar o projeto

Com Python 3.10+, PHP e Node.js no `PATH`:

```sh
python tests/verificar_frontend.py
```

O teste verifica sintaxe e referências a recursos locais sem conectar ao banco. A imagem principal do login está em WebP sem perda para diminuir a transferência sem alterar os pixels da imagem original.
