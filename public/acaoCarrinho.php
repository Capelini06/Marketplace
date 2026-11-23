<?php

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . "/../include/funcoes.php";
require_once __DIR__ . "/../config/conexao.php";

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    http_response_code(400);
    exit("Requisição invalida!");
}

validarCsrf();

$acao = trim($_POST["acao"] ?? "");
$id = (int) ($_POST["id"] ?? 0);
$qtd = (int) ($_POST["qtd"] ?? 1);

switch($acao){
    case "adicionar":
        $stmt = $pdo->prepare("SELECT 1 FROM produtos WHERE id = :id");
        $stmt -> execute([":id" => $id]);
        if($stmt->fetchColumn()){
            adicionarCarrinho($id, $qtd);
            }
        header("Location: index.php");
        exit;
    case "atualizar":
        definirCarrinho($id, $qtd);
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