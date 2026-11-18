# Voltra Tech - Loja de Eletrônicos

Marketplace em PHP + PostgreSQL (Supabase) de produtos eletrônicos (smartphones, notebooks, áudio e acessórios).

## Tecnologias

- PHP 8+ com PDO (extensões `pdo_pgsql` e `fileinfo` habilitadas)
- PostgreSQL hospedado no [Supabase](https://supabase.com)
- HTML + CSS puro (`public/assets/css/style.css`)

## Como rodar

1. Crie um projeto no Supabase e rode o arquivo `schema-supabase.sql` inteiro no **SQL Editor**.
   Ele cria as tabelas, ativa o RLS e insere dados de exemplo (categorias, produtos e um usuário admin).
2. Copie `.env.example` para `.env` (na raiz do projeto) e preencha com os dados de conexão do Supabase
   (em **Project Settings → Database → Connection string**):

   ```ini
   DB_HOST=aws-0-REGIAO.pooler.supabase.com
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres.ID_DO_PROJETO
   DB_PASS="coloque_a_senha_aqui"
   ```

3. Sirva **somente a pasta `public/`** (ela é a raiz do site):

   ```bash
   php -S localhost:8000 -t public
   ```

   No Apache, aponte o `DocumentRoot` para `public/`.

4. Acesse `http://localhost:8000`.

> O `.env` está no `.gitignore` e nunca deve ser commitado. Como ele fica fora de `public/`,
> não pode ser acessado pelo navegador.

## Estrutura

```text
├── public/                  # Raiz do site (único diretório exposto)
│   ├── index.php            # Página inicial (destaques)
│   ├── loja.php             # Listagem de produtos com busca (?busca=)
│   ├── produtos.php         # Detalhe de um produto (?id=)
│   ├── login.php            # Formulário de login
│   ├── cadastro.php         # Cadastro de novos clientes
│   ├── auth/
│   │   ├── autenticar.php   # Processa o login (POST)
│   │   └── logout.php       # Encerra a sessão
│   ├── admin/               # Painel administrativo (apenas admin)
│   │   ├── painelProdutos.php  # Lista de produtos + alerta de estoque baixo
│   │   ├── form.php         # Cadastro/edição de produto (com upload de imagem)
│   │   ├── salvar.php       # Grava o produto e a imagem (POST)
│   │   └── excluir.php      # Confirmação e exclusão de produto
│   ├── assets/css/style.css
│   ├── uploads/produtos/    # Imagens enviadas pelo painel
│   └── .htaccess            # Bloqueia arquivos ocultos, .sql e .git no Apache
├── config/conexao.php       # Conexão PDO lendo o .env
├── include/
│   ├── cabecalho.php        # Header + navegação
│   ├── rodape.php           # Footer
│   └── funcoes.php          # Helpers: prefixo(), e(), exigirLogin/Admin/Cliente(), CSRF
├── schema-supabase.sql      # Schema + dados iniciais
└── .env.example
```

## Painel administrativo

Após o login, o admin é redirecionado para `admin/painelProdutos.php`, onde pode:

- listar todos os produtos com categoria, preço, garantia e estoque;
- ver destacados os produtos com estoque igual ou abaixo do mínimo;
- cadastrar e editar produtos (o slug é gerado automaticamente a partir do nome);
- enviar uma imagem por produto (JPG, PNG ou WEBP, até 2 MB, tipo validado por `finfo`);
- excluir produtos (com tela de confirmação).

## Segurança

- Senhas armazenadas com `password_hash` (rehash automático no login quando necessário).
- Sessão regenerada (`session_regenerate_id`) após o login.
- Consultas com *prepared statements* (PDO).
- Saída escapada com o helper `e()` (`htmlspecialchars`).
- Proteção **CSRF** nos formulários de login, cadastro e produto via
  `csrfToken()` e `validarCsrf()` em `include/funcoes.php`.
- Rotas de admin protegidas por `exigirAdmin()`.
- Uploads salvos com nome aleatório e extensão definida pelo tipo MIME real do arquivo.
- Apenas `public/` é servido; `.htaccess` ainda bloqueia arquivos ocultos, `.sql` e `.git`.
- RLS ativado em todas as tabelas do Supabase (o PHP conecta como `postgres`, que ignora o RLS).

## Usuário administrador

O schema cria um admin com o e-mail `admin@email.com`. Troque a senha após o primeiro acesso.

## Status

- [x] Página inicial e detalhe de produto
- [x] Cadastro, login e logout
- [x] Proteção CSRF
- [x] Página da loja com busca (`loja.php`)
- [x] Painel administrativo (CRUD de produtos + upload de imagem)
- [ ] Filtro por categoria na loja
- [ ] Perfil do usuário
- [ ] Carrinho e pedidos
