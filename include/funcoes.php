<?php

// Prefixo relativo até a raiz de public/ ("../" para páginas em admin/ ou auth/)
function prefixo(): string
{
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    return (str_contains($script, '/admin/') || str_contains($script, '/auth/')) ? '../' : '';
}

function e(?string $valor): string
{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function preco($preco)
{
    return "R$" . number_format($preco, 2, ",", ".");
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
        exit("Requisicão inválida");
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


// Busca os produtos do carrinho no banco (preço sempre vem do banco)
function itensCarrinho(PDO $pdo): array
{
    $carrinho = carrinho();
    if (!$carrinho)
        return [];

    $ids = array_keys($carrinho);
    $marcadores = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare(
        "SELECT id, nome, preco FROM produtos WHERE id IN ($marcadores)"
    );
    $stmt->execute($ids);

    $itens = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $p) {
        $qtd = (int) $carrinho[(int) $p['id']];
        $preco = (float) $p['preco'];
        $itens[] = [
            'id' => (int) $p['id'],
            'nome' => $p['nome'],
            'preco' => $preco,
            'qtd' => $qtd,
            'subtotal' => $qtd * $preco,
        ];
    }
    return $itens;
}
