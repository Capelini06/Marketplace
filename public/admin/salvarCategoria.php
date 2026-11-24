<?php

session_start();
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();
validarCsrf();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painelProdutos.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$nome = trim($_POST["nome"] ?? "");


function gerarSlug(string $texto): string {
    $texto = iconv("UTF-8", "ASCII//TRANSLIT", $texto);
    $texto = strtolower(trim(preg_replace("/[^A-Za-z0-9]+/", "-", $texto), "-"));
    return $texto !== "" ? $texto : "produto";
}

$slug = gerarSlug($nome) . ($id ? "-$id" : "-" . bin2hex(random_bytes(3)));

if ($nome === "") {
    exit("Dados inválidos!");
}

$categoria = [
    ":nome" => $nome,
    ":slug" => $slug
];

if (!$id){
    $sql = "INSERT INTO categorias(nome, slug) VALUES (:nome, :slug) RETURNING id";
}
try{
    $banco = $pdo->prepare($sql);
    $banco->execute($categoria);

    // Produto novo: pega o id gerado pelo RETURNING id. Edição: usa o id do formulário.
    $categoriaId = $id ?: (int) $banco->fetchColumn();
} catch(PDOException $erro){
    error_log($erro);
    // Temporário, para depuração: mostra o erro real. Remova antes de entregar.
    exit("Falha ao atualizar o banco de dados: " . e($erro->getMessage()));
}

header("Location: painelProdutos.php");
exit;