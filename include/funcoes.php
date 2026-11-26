<?php

// Prefixo relativo até a raiz de public/ ("../" para páginas em admin/, auth/ ou pagamento/)
function prefixo(): string
{
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    foreach (['/admin/', '/auth/', '/pagamento/'] as $pasta) {
        if (str_contains($script, $pasta)) {
            return '../';
        }
    }
    return '';
}

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function preco($preco)
{
    return "R$ " . number_format((float) $preco, 2, ",", ".");
}

// Converte um valor em reais (ex.: 2499.90) para centavos (249990), como a InfinitePay espera
function centavos($valor): int
{
    return (int) round((float) $valor * 100);
}

function exigirLogin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . prefixo() . 'login.php');
        exit;
    }
}

// Verifica se o usuario que esta acessando é o admin ou não, se não for, vai para o login
function exigirAdmin(): void
{
    // Verifica se o estado da sessão é diferente de ativo, se for, se cria uma sessão
    if (session_status() !== PHP_SESSION_ACTIVE)
        session_start();
    if (
        !isset(
        $_SESSION["usuario_id"]
    )
        ||
        ($_SESSION["tipo"] ?? "")
        !== "admin"
    ) {
        header("Location: " . prefixo() . "login.php");
        exit;
    }
}

function exigirCliente(): void
{
    exigirLogin();

    if (($_SESSION['tipo'] ?? '') !== 'cliente') {
        header('Location: ' . prefixo() . 'admin/painelProdutos.php');
        exit;
    }
}
function csrfToken(): string
{
    if (empty($_SESSION["csrf"])) {
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function validarCsrf(): void
{
    if (!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")) {
        http_response_code(403);
        exit("Requisição inválida");
    }
}

function carrinho(): array
{
    return $_SESSION["carrinho"] ?? [];
}

function adicionarCarrinho(int $id, int $qtd = 1): void
{
    if ($id <= 0 || $qtd <= 0)
        return;
    $atual = $_SESSION["carrinho"][$id] ?? 0;
    $_SESSION["carrinho"][$id] = min(99, $atual + $qtd);
}

function definirCarrinho(int $id, int $qtd): void
{
    if ($qtd <= 0) {
        unset($_SESSION["carrinho"][$id]);
        return;
    }
    $_SESSION["carrinho"][$id] = min(99, $qtd);
}

function removerCarrinho(int $id): void
{
    unset($_SESSION["carrinho"][$id]);
}

function limparCarrinho(): void
{
    unset($_SESSION["carrinho"]);
}

function totalCarrinho(): int
{
    return array_sum($_SESSION["carrinho"] ?? []);
}


// Busca os produtos do carrinho no banco (preço sempre vem do banco).
// Remove da sessão produtos que não existem mais, estão inativos ou sem estoque,
// e limita a quantidade ao estoque disponível.
function itensCarrinho(PDO $pdo): array
{
    $carrinho = carrinho();
    if (!$carrinho)
        return [];

    $ids = array_map('intval', array_keys($carrinho));
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT id, nome, preco, estoque FROM produtos
         WHERE id IN ($marcadores) AND ativo = TRUE AND estoque > 0"
    );
    $stmt->execute($ids);

    $itens = [];
    $validos = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $id = (int) $p['id'];
        $qtd = min((int) $carrinho[$id], (int) $p['estoque']);
        $preco = (float) $p['preco'];
        $validos[$id] = $qtd;
        $itens[] = [
            'id' => $id,
            'nome' => $p['nome'],
            'preco' => $preco,
            'qtd' => $qtd,
            'estoque' => (int) $p['estoque'],
            'subtotal' => $qtd * $preco,
        ];
    }

    // Mantém a sessão coerente com o que realmente pode ser comprado
    $_SESSION['carrinho'] = $validos;

    return $itens;
}

// Marca o pedido como pago e baixa o estoque, uma única vez (idempotente).
// Usado pelo webhook e pela página de retorno (obrigado.php).
function marcarPedidoPago(PDO $pdo, int $pedidoId): void
{
    $pdo->beginTransaction();
    try {
        $up = $pdo->prepare(
            "UPDATE pedidos SET status = 'pago', pago_em = NOW()
             WHERE id = ? AND status = 'aguardando_pagamento'"
        );
        $up->execute([$pedidoId]);

        // Só baixa o estoque se este processo foi quem mudou o status
        if ($up->rowCount() === 1) {
            $pdo->prepare(
                "UPDATE produtos p SET estoque = GREATEST(p.estoque - i.quantidade, 0)
                 FROM itens_pedido i
                 WHERE i.pedido_id = ? AND i.produto_id = p.id"
            )->execute([$pedidoId]);
        }

        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}
