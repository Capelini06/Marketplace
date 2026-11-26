<?php

session_start();
require_once __DIR__ . "/../../include/funcoes.php";
exigirAdmin();

require_once __DIR__ . "/../../config/conexao.php";

$cats = ["nome"=>"", "ativo"=>1];

$titulo = ("Cadastrar") . " Categoria | Admin";
include __DIR__ . "/../../include/cabecalho.php";
?>

<main class="section narrow">
    <h1>
        Cadastrar Categoria
    </h1>
    <form class="adminform" method="post" action="salvarCategoria.php">
        <input type="hidden" name="csrf" value="<?= csrfToken()?>">
        <label  for="nome">Nome: </label>
        <input  type="text"
                name="nome"
                id="nome"
                required
                maxlength="80"
                value="<?= e($cats["nome"]) ?>">

        <br><br>

        <button class="btn">Enviar</button>
    </form>
    <a href="painelProdutos.php">Voltar</a>
</main>

<?php include __DIR__ . "/../../include/rodape.php"; ?>