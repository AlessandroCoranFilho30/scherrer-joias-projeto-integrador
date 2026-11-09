# Scherrer Joias

Loja fictícia criada como projeto educativo da disciplina Projeto Integrador II, na Fatec Americana.

## Requisitos

- PHP 8.1 ou superior
- Extensão PDO_SQLite habilitada

## Executar localmente

Na pasta do projeto, inicie o servidor embutido do PHP:

```powershell
php -S localhost:8000
```

Abra `http://localhost:8000` no navegador. O banco SQLite é criado automaticamente em `data/loja.sqlite` na primeira visita; a conta e a senha ficam armazenadas com hash. Não há usuário de demonstração predefinido: crie uma conta pela opção **Criar conta**.

## Funcionalidades

- Catálogo pesquisável e organizado por categoria, com página dinâmica para cada produto.
- Cadastro e login de clientes, com senha protegida por `password_hash`.
- Carrinho persistente por conta, com atualização e remoção de quantidades.
- Finalização de compra simulada, com opção de retirada ou entrega fictícia e histórico registrado no banco.
- Consulta do histórico de compras fictícias da conta.

As compras são exclusivamente demonstrativas: o sistema não solicita dados de pagamento, não cobra e não envia pedidos reais.
