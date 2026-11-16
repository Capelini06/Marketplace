# Voltra Tech - Loja de Eletrônicos

Marketplace em PHP + PostgreSQL (Supabase) de produtos eletrônicos (smartphones, notebooks, áudio e acessórios).

## Tecnologias

- PHP 8+ com PDO (extensão `pdo_pgsql` habilitada)
- PostgreSQL hospedado no [Supabase](https://supabase.com)
- HTML + CSS puro (`assets/css/style.css`)

## Como rodar

1. Crie um projeto no Supabase e rode o arquivo `schema-supabase.sql` inteiro no **SQL Editor**.
   Ele cria as tabelas, ativa o RLS e insere dados de exemplo (categorias, produtos e um usuário admin).
2. Copie `.env.example` para `.env` e preencha com os dados de conexão do Supabase
   (em **Project Settings → Database → Connection string**):

   ```ini
   DB_HOST=aws-0-REGIAO.pooler.supabase.com
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres.ID_DO_PROJETO
   DB_SENHA="coloque_a_senha_aqui"
   ```

3. Sirva a pasta do projeto pela raiz:

   ```bash
   php -S localhost:8000
   ```

4. Acesse `http://localhost:8000`.

> O `.env` está no `.gitignore` e nunca deve ser commitado.

## Estrutura

```text
├── index.php            # Página inicial (últimos produtos)
├── produtos.php         # Detalhe de um produto (?id=)
├── login.php            # Formulário de login
├── cadastro.php         # Cadastro de novos clientes
├── meuPerfil.php        # Perfil do usuário logado / logout
├── auth/
│   ├── autenticar.php   # Processa o login (POST)
│   └── logout.php       # Encerra a sessão (POST)
├── admin/               # Painel administrativo (em desenvolvimento)
├── config/conexao.php   # Conexão PDO lendo o .env
├── include/
│   ├── cabecalho.php    # Header + navegação
│   ├── rodape.php       # Footer
│   └── funcoes.php      # Helpers: e(), exigirAdmin(), CSRF
├── assets/css/style.css
├── schema-supabase.sql  # Schema + dados iniciais
└── .htaccess            # Bloqueia .env, .sql e .git no Apache
```

## Segurança

- Senhas armazenadas com `password_hash` (bcrypt).
- Consultas com *prepared statements* (PDO).
- Saída escapada com o helper `e()` (`htmlspecialchars`).
- Proteção **CSRF** em todos os formulários POST (login, cadastro e logout) via
  `csrfToken()` e `validarCsrf()` em `include/funcoes.php`.
- Rotas de admin protegidas por `exigirAdmin()`.
- `.htaccess` bloqueia acesso a arquivos ocultos, `.sql` e à pasta `.git`.
- RLS ativado em todas as tabelas do Supabase (o PHP conecta como `postgres`, que ignora o RLS).

## Usuário administrador

O schema cria um admin com o e-mail `admin@email.com`. Troque a senha após o primeiro acesso.

## Status

- [x] Página inicial e detalhe de produto
- [x] Cadastro, login e logout
- [x] Proteção CSRF
- [ ] Página da loja (`loja.php`)
- [ ] Painel administrativo (CRUD de produtos)
- [ ] Carrinho e pedidos
