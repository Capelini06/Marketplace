<?php

session_start();
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();

require_once __DIR__ . "/../../config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id)exit("ID invalido!");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $server = $pdo->prepare("DELETE FROM produtos WHERE id=:id");
    $server->execute([":id" => $id]);
    header("Location: painelProdutos.php");
    exit;
}
$server = $pdo->prepare("SELECT nome FROM produtos WHERE id = :id");
$server->execute([":id" => $id]);
$produto = $server->fetch();

if(!$produto) exit("Produto não encontrado!");

include_once __DIR__ . "/../../include/cabecalho.php";?>

<main class="section narrow">
    <h1>Confirmar exclusão</h1>
    <p>Deseja realmente excluir <?= e($produto["nome"])?>? </p>
    <form method="post">
        <button class="btn danger">Confirmar exclusão</button>
    </form>
    <p>
        <a href="painelProdutos.php">Cancelar</a>
    </p>
</main>

<?php include_once __DIR__ . "/../../include/rodape.php";?>