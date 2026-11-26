<?php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . "/../include/funcoes.php";
require_once __DIR__ . "/../config/conexao.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(400);
    exit("Requisição inválida!");
}

validarCsrf();

$acao = trim($_POST["acao"] ?? "");
$id = (int) ($_POST["id"] ?? 0);
$qtd = (int) ($_POST["qtd"] ?? 1);

// Página para onde voltar depois de adicionar (só aceita páginas locais simples)
$voltar = (string) ($_POST["voltar"] ?? "");
if (!preg_match('/^[a-zA-Z]+\.php(\?id=\d+)?$/', $voltar)) {
    $voltar = "carrinho.php";
}

// Estoque atual do produto (0 se não existir ou estiver inativo)
function estoqueDisponivel(PDO $pdo, int $id): int
{
    $stmt = $pdo->prepare("SELECT estoque FROM produtos WHERE id = :id AND ativo = TRUE");
    $stmt->execute([":id" => $id]);
    return (int) $stmt->fetchColumn();
}

switch ($acao) {
    case "adicionar":
        $estoque = estoqueDisponivel($pdo, $id);
        if ($estoque > 0) {
            adicionarCarrinho($id, $qtd);
            // Não deixa passar do estoque
            definirCarrinho($id, min(carrinho()[$id] ?? 0, $estoque));
        }
        header("Location: " . $voltar);
        exit;
    case "atualizar":
        definirCarrinho($id, min($qtd, estoqueDisponivel($pdo, $id)));
        break;
    case "remover":
        removerCarrinho($id);
        break;
    case "limpar":
        limparCarrinho();
        break;
}
header("Location: carrinho.php");
exit;
