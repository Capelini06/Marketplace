<?php

session_start();
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painelProdutos.php");
    exit;
}

validarCsrf();

$nome = trim($_POST["nome"] ?? "");

if ($nome === "") {
    exit("Dados inválidos!");
}

function gerarSlug(string $texto): string {
    $texto = iconv("UTF-8", "ASCII//TRANSLIT", $texto);
    $texto = strtolower(trim(preg_replace("/[^A-Za-z0-9]+/", "-", $texto), "-"));
    return $texto !== "" ? $texto : "categoria";
}

// Sufixo aleatório evita conflito com a restrição UNIQUE do slug
$slug = gerarSlug($nome) . "-" . bin2hex(random_bytes(3));

try {
    $banco = $pdo->prepare("INSERT INTO categorias (nome, slug) VALUES (:nome, :slug)");
    $banco->execute([
        ":nome" => $nome,
        ":slug" => $slug
    ]);
} catch (PDOException $erro) {
    error_log($erro->getMessage());
    exit("Não foi possível salvar a categoria.");
}

header("Location: painelProdutos.php");
exit;
