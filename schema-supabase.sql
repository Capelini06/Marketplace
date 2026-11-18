-- nome=
-- categoria_id=
-- slug=
-- descricao=
-- garantia_meses=
-- preco=
-- estoque=
-- estoque_minimo=
-- ativo=


-- ---------------------------------------------------------------
-- Schema Voltra Tech - versão PostgreSQL (Supabase)
-- Rode este arquivo inteiro no SQL Editor do Supabase.
-- ---------------------------------------------------------------

-- Grava datas no horário de Brasília em vez de UTC
-- (vale para conexões abertas depois deste comando)
ALTER DATABASE postgres SET timezone TO 'America/Sao_Paulo';
SET timezone TO 'America/Sao_Paulo'; -- aplica também aos INSERTs deste arquivo

-- citext = texto que compara sem diferenciar maiúsculas/minúsculas
-- (mantém o comportamento do MySQL para o e-mail)
CREATE EXTENSION IF NOT EXISTS citext;

CREATE TABLE usuarios (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email CITEXT NOT NULL UNIQUE CHECK (char_length(email) <= 150),
    senha VARCHAR(255) NOT NULL,
    telefone VARCHAR(20) NULL,
    tipo VARCHAR(10) NOT NULL DEFAULT 'cliente' CHECK (tipo IN ('admin', 'cliente')),
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE enderecos (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES usuarios(id),
    cep CHAR(8) NOT NULL,
    rua VARCHAR(150) NOT NULL,
    numero VARCHAR(10) NOT NULL,
    complemento VARCHAR(100) NULL,
    bairro VARCHAR(100) NOT NULL,
    cidade VARCHAR(100) NOT NULL,
    uf CHAR(2) NOT NULL
);

CREATE TABLE categorias (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    nome VARCHAR(80) NOT NULL,
    slug VARCHAR(90) NOT NULL UNIQUE,
    ativo BOOLEAN NOT NULL DEFAULT TRUE
);

CREATE TABLE produtos (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    categoria_id INT NOT NULL REFERENCES categorias(id),
    nome VARCHAR(120) NOT NULL,
    slug VARCHAR(140) NOT NULL UNIQUE,
    marca VARCHAR(60) NULL,
    modelo VARCHAR(80) NULL,
    descricao TEXT NULL,
    especificacoes TEXT NULL,
    garantia_meses INT NOT NULL DEFAULT 12,
    preco NUMERIC(10,2) NOT NULL,
    estoque INT NOT NULL DEFAULT 0,
    estoque_minimo INT NOT NULL DEFAULT 3,
    comprimento_cm NUMERIC(5,1) NULL,
    ativo BOOLEAN NOT NULL DEFAULT TRUE,
    voltagem VARCHAR(20) NULL,
    peso_g INT NULL,
    altura_cm NUMERIC(5,1) NULL,
    largura_cm NUMERIC(5,1) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NULL
);

-- Substitui o "ON UPDATE CURRENT_TIMESTAMP" do MySQL
CREATE OR REPLACE FUNCTION atualizar_atualizado_em()
RETURNS TRIGGER AS $$
BEGIN
    NEW.atualizado_em = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ LANGUAGE plpgsql SET search_path = '';

CREATE TRIGGER produtos_atualizado_em
BEFORE UPDATE ON produtos
FOR EACH ROW EXECUTE FUNCTION atualizar_atualizado_em();

CREATE TABLE produto_imagens (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    produto_id INT NOT NULL REFERENCES produtos(id) ON DELETE CASCADE,
    caminho VARCHAR(255) NOT NULL,
    ordem INT NOT NULL DEFAULT 0
);

CREATE TABLE pedidos (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    usuario_id INT NOT NULL REFERENCES usuarios(id),
    status VARCHAR(25) NOT NULL DEFAULT 'aguardando_pagamento'
        CHECK (status IN ('aguardando_pagamento', 'pago', 'em_separacao', 'enviado', 'entregue', 'cancelado')),
    subtotal NUMERIC(10,2) NOT NULL,
    frete NUMERIC(10,2) NOT NULL DEFAULT 0,
    desconto NUMERIC(10,2) NOT NULL DEFAULT 0,
    total NUMERIC(10,2) NOT NULL,
    cep CHAR(8) NOT NULL,
    endereco_completo VARCHAR(400) NOT NULL,
    codigo_rastreio VARCHAR(50) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    pago_em TIMESTAMP NULL
);

CREATE TABLE itens_pedido (
    id INT GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
    pedido_id INT NOT NULL REFERENCES pedidos(id),
    produto_id INT NOT NULL REFERENCES produtos(id),
    nome_produto VARCHAR(120) NOT NULL,
    preco_unitario NUMERIC(10,2) NOT NULL,
    quantidade INT NOT NULL
);

-- No PostgreSQL, chaves estrangeiras não ganham índice automaticamente
CREATE INDEX ON enderecos (usuario_id);
CREATE INDEX ON produtos (categoria_id);
CREATE INDEX ON produto_imagens (produto_id);
CREATE INDEX ON pedidos (usuario_id);
CREATE INDEX ON itens_pedido (pedido_id);
CREATE INDEX ON itens_pedido (produto_id);

-- ---------------------------------------------------------------
-- Segurança: bloqueia acesso pela Data API (anon/authenticated).
-- O PHP conecta como "postgres", que ignora o RLS, então nada quebra.
-- ---------------------------------------------------------------
ALTER TABLE usuarios        ENABLE ROW LEVEL SECURITY;
ALTER TABLE enderecos       ENABLE ROW LEVEL SECURITY;
ALTER TABLE categorias      ENABLE ROW LEVEL SECURITY;
ALTER TABLE produtos        ENABLE ROW LEVEL SECURITY;
ALTER TABLE produto_imagens ENABLE ROW LEVEL SECURITY;
ALTER TABLE pedidos         ENABLE ROW LEVEL SECURITY;
ALTER TABLE itens_pedido    ENABLE ROW LEVEL SECURITY;

-- ---------------------------------------------------------------
-- Dados iniciais
-- ---------------------------------------------------------------

-- Usuário administrador (mesmo hash de senha usado no banco anterior)
INSERT INTO usuarios (nome, email, senha, tipo) VALUES
('admin', 'admin@email.com', '$2y$12$56JDP0.Mce/VSmU80ZFlJOe5ztrV7cLEKzV386Slc0KvUJxBG2vwC', 'admin');

INSERT INTO categorias (nome, slug) VALUES
('Smartphones', 'smartphones'),
('Notebooks', 'notebooks'),
('Áudio', 'audio'),
('Acessórios', 'acessorios');

INSERT INTO produtos (categoria_id, nome, slug, marca, modelo, descricao, especificacoes, voltagem, garantia_meses, preco, estoque, peso_g, altura_cm, largura_cm, comprimento_cm) VALUES
(1, 'Smartphone Voltra X1', 'smartphone-voltra-x1', 'Voltra', 'X1',
 'Tela AMOLED de 6,5", câmera tripla de 50 MP e bateria de 5000 mAh.',
 '256 GB de armazenamento, 8 GB de RAM, 5G, Android 15', 'bateria', 12, 2499.90, 20, 190, 16.2, 7.5, 0.8),
(2, 'Notebook Voltra Pro 14', 'notebook-voltra-pro-14', 'Voltra', 'Pro 14',
 'Notebook leve e potente para trabalho e estudos.',
 'Processador 8 núcleos, 16 GB de RAM, SSD 512 GB, tela 14" Full HD', 'bivolt', 12, 4899.90, 10, 1400, 1.8, 31.5, 22.0),
(3, 'Fone Bluetooth Pulse', 'fone-bluetooth-pulse', 'Voltra', 'Pulse',
 'Fone sem fio com cancelamento de ruído ativo.',
 'Bluetooth 5.3, até 30h de bateria, carregamento USB-C', 'bateria', 6, 399.90, 35, 250, 18.0, 16.0, 8.0),
(4, 'Carregador Turbo 65W', 'carregador-turbo-65w', 'Voltra', 'Turbo 65',
 'Carregador rápido compatível com celulares e notebooks.',
 'Potência de 65W, 2 portas USB-C e 1 USB-A', 'bivolt', 6, 189.90, 50, 120, 6.0, 6.0, 3.0);

INSERT INTO produto_imagens (produto_id, caminho, ordem) VALUES
(1, 'assets/img/smartphone-x1.jpg', 0),
(2, 'assets/img/notebook-pro14.jpg', 0),
(3, 'assets/img/fone-pulse.jpg', 0),
(4, 'assets/img/carregador-turbo.jpg', 0);
