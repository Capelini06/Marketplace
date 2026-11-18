<?php

session_start();
require_once __DIR__ . "/../../include/funcoes.php";
exigirAdmin();

require_once __DIR__ . "/../../config/conexao.php";
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$p=["nome"=>"", "categoria_id"=>"", "descricao"=>"", "garantia_meses"=>12,"preco"=>"", "estoque"=>0, "estoque_minimo"=>0, "ativo"=>1];

if($id){
    $stmt=$pdo->prepare("SELECT * FROM produtos WHERE id=:id");
    $stmt->execute([":id"=>$id]);
    $p=$stmt->fetch()?:$p;
}
$cats=$pdo->query("SELECT * FROM categorias ORDER BY nome")->fetchAll();

$titulo = ($id ? "Editar" : "Cadastrar") . " Produto | Admin";
include __DIR__ . "/../../include/cabecalho.php";
?>

<main class="section narrow">
    <h1>
        <?= $id?"editar":"cadastrar"?> Produto
    </h1>
    <form class="adminform" method="post" action="salvar.php" enctype="multipart/form-data">
        <input  type="hidden" name="id" id="id" value="<?= (int)($p["id"]??0)?>">
        <input type="hidden" name="csrf" value="<?= csrfToken()?>">
        <label  for="nome">Nome: </label>
        <input  type="text"
                name="nome"
                id="nome"
                required
                maxlength="120"
                value="<?= e($p["nome"]) ?>">

        <br><br>

        <label  for="categoria_id">Categoria: </label>
        <select name="categoria_id" id="categoria_id" required>
            <?php foreach($cats as $c):?>
            <option value="<?= (int) $c["id"] ?>" 
                    <?=((int) $p["categoria_id"] === (int) $c["id"])?"selected":""?> >
                <?= e($c["nome"])?>
            </option>
            <?php endforeach;?>
        </select>
        
        <br><br>

        <label  for="descricao">Descrição: </label>
        <textarea name="descricao" id="descricao"><?= e($p["descricao"]) ?></textarea>

        <br><br>

        <label  for="garantia_meses">Garantia(meses): </label>
        <input  type="number"
                name="garantia_meses"
                id="garantia_meses"
                step="1"
                required
                value="<?= (int) $p["garantia_meses"] ?>">

        <br><br>

        <label  for="preco">Preço: </label>
        <input  type="number"
                name="preco"
                id="preco"
                min="0"
                step="0.01"
                required
                value="<?= (float) $p["preco"] ?>">

        <br><br>

        <label  for="estoque">Estoque: </label>
        <input  type="number"
                name="estoque"
                id="estoque"
                step="1"
                min="0"
                required
                value="<?= (int) $p["estoque"] ?>">

        <br><br>

        <label  for="estoque_minimo">Estoque minimo: </label>
        <input  type="number"
                name="estoque_minimo"
                id="estoque_minimo"
                step="1"
                min="0"
                required
                value="<?= (int) $p["estoque_minimo"] ?>">

        <br><br>

        <label  for="imagens">Imagens: </label>
        <input  type="file" name="imagem" accept="image/jpeg,image/png,image/webp">

        <button class="btn">Enviar</button>
    </form>
    <a href="painelProdutos.php">Voltar</a>
</main>

<?php include __DIR__ . "/../../include/rodape.php"; ?>