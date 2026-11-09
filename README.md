# Scherrer Joias 💎

## Integrantes do grupo

- Alessandro Coran Filho
- Leonardo Sherrer
- Caio Diodato
- Matheus de Jesus Souza
- Mateus Ferreira

<div align="center">
<img src="img/logo.png">
</div>

## Sobre

**Grupo da matéria de Projeto Integrador II, Fatec Americana.**

Página fictícia da Scherrer joias, uma empresa comerciante de jóias em Limeira, página criada para fins educativos durante as aulas de Projeto integrador II no segundo semestre do curso de Análise e Desenvolvimento de Sistemas da Fatec Americana. Site construído com HTML, CSS, PHP e SQLite, utilizando os recursos aprendidos durante a aula de Desenvolvimento Web.

### Problema

O principal problema enfrentado pela Scherrer é a *falta de presença digital* e de conhecimento para aproveitar esses meios. Muitos clientes gostariam de obter informações e realizar compras online, sem precisar falar com atendentes ou ir até a loja

## ✅ Requisitos

### Requisitos funcionais

| Código | Requisito | Prioridade | Descrição |
|---|---|---|---|
| RF01 | Cadastro de Clientes | Essencial | Permitir o cadastro de novos clientes com informações de acesso. |
| RF02 | Login de Usuário | Essencial | Permitir que clientes façam login com e-mail e senha. |
| RF03 | Visualização do Catálogo | Essencial | Exibir todo o catálogo de produtos disponíveis. |
| RF04 | Exibição Detalhada dos Produtos | Essencial | Mostrar descrição, imagens e preços dos produtos. |
| RF05 | Organização por Categorias | Importante | Dividir os produtos em categorias, como anéis, brincos e braceletes. |
| RF06 | Pesquisa por Produtos | Importante | Permitir a pesquisa de produtos por nome ou categoria. |
| RF07 | Carrinho de Compras | Essencial | Adicionar e remover produtos do carrinho. |
| RF08 | Finalização de Compras | Essencial | Permitir que clientes realizem compras online. |
| RF09 | Retirada na Loja | Importante | Selecionar a opção de retirada presencial na loja. |
| RF10 | Entrega de Produtos | Desejável | Permitir o envio de produtos por entregadores. |
| RF11 | Comunicação com Atendentes | Desejável | Permitir conversa entre clientes e atendentes. |
| RF12 | Gerenciamento de Produtos | Importante | Permitir que administradores adicionem, editem e removam produtos do catálogo. |

### Requisitos não funcionais

| Código | Requisito | Descrição |
|---|---|---|
| RNF01 | Disponibilidade do Sistema | O sistema deve ficar disponível 99% do tempo. |
| RNF02 | Tempo de Carregamento | As páginas devem carregar em menos de 4 segundos. |
| RNF03 | Segurança de Dados | O sistema deve criptografar os dados de login dos clientes (e-mail e senha). |
| RNF04 | Interface Intuitiva | O sistema deve ter uma interface simples e de fácil uso. |

## Funcionalidades implementadas

- Catálogo pesquisável e organizado por categoria (RF03, RF05 e RF06), com página dinâmica para cada produto (RF04).
- Cadastro e login de clientes, com senha protegida por `password_hash` (RF01 e RF02).
- Carrinho persistente por conta, com atualização e remoção de quantidades e controle de estoque (RF07).
- Finalização de compra simulada, com opção de retirada na loja (RF09) ou entrega fictícia (RF10), baixa de estoque e histórico registrado no banco (RF08).
- Consulta do histórico de compras fictícias da conta, com data, endereço de entrega e itens do pedido.
- Canal de conversa entre cliente e atendente (RF11): o cliente envia mensagens em **Contato** e o atendente responde pelo painel administrativo.
- Gerenciamento de produtos pelo administrador (RF12): cadastrar, editar, remover e ajustar o estoque das peças.

As compras são exclusivamente demonstrativas: o sistema não solicita dados de pagamento, não cobra e não envia pedidos reais.

## Requisitos técnicos

- PHP 8.1 ou superior
- Extensão PDO_SQLite habilitada

## Executar localmente

Na pasta do projeto, inicie o servidor embutido do PHP:

```powershell
php -S localhost:8000
```

Se o PowerShell não reconhecer `php`, use o executável do XAMPP (se instalado no caminho padrão):

```powershell
& 'C:\xampp\php\php.exe' -S localhost:8000
```

Mantenha o servidor em execução e abra `http://localhost:8000` no navegador. Não execute páginas como `php entrar.php`: a autenticação depende de uma requisição HTTP, sessões e cookies do navegador. O banco SQLite é criado automaticamente em `data/loja.sqlite` na primeira visita; a conta e a senha ficam armazenadas com hash. Não há usuário de demonstração predefinido: crie uma conta pela opção **Criar conta**.

## Publicar online (InfinityFree, grátis)

A Vercel, Netlify e GitHub Pages não servem para PHP com SQLite (serverless/estático). A hospedagem gratuita [InfinityFree](https://infinityfree.com) roda o projeto sem alterações, pois os drivers `pdo_sqlite` já vêm habilitados no PHP:

1. Crie uma conta em infinityfree.com e um subdomínio gratuito (ex.: `scherrerjoias.infinityfreeapp.com`).
2. No painel de controle, em **PHP Configuration**, selecione a versão mais nova disponível (o projeto exige PHP 8.1 ou superior).
3. Envie todos os arquivos do projeto para a pasta `htdocs/` (pelo gerenciador de arquivos online ou por FTP: host `ftpupload.net`, porta 21). O banco SQLite é criado automaticamente em `data/` na primeira visita; o `.htaccess` na raiz impede o download do banco pela web.
4. Ative o SSL gratuito no painel para acessar por HTTPS.
5. Acesse o site e crie as contas necessárias; o painel administrativo fica liberado conforme a configuração de `ADMIN_EMAILS` em `includes/app.php`.

Para atualizar o site, envie apenas os arquivos alterados por FTP. Se a escrita no SQLite apresentar instabilidade nessa hospedagem, o caminho mais robusto é migrar o banco para o MySQL que eles fornecem.

---

<div align="start">
  <a href="#"><img src="https://img.shields.io/badge/HTML5-E34F26?style=for-the-badge&logo=html5&logoColor=white" /></a>
  <p>• Imagens <br/>
• Botões <br/>
• Cabeçalho</p>
  <a href="#"><img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" /></a>
  <p>• Flexbox<br/>
• Box Model<br/>
• Tabelas <br />
• Estilização</p>
  <a href="#"><img src="https://img.shields.io/badge/PHP-484C89?style=for-the-badge&logo=php&logoColor=white" /></a>
  <p>• Adição de itens ao carrinho<br/>
• Busca de itens<br/>
• Histórico de compras<br />
• Opções de cadastro e login</p>
<a href="#"><img src="https://img.shields.io/badge/SQL-003B57?style=for-the-badge&logo=mysql&logoColor=white" /></a>
</div>
