<?php

session_start();
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();

require_once __DIR__ . "/../../config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id) exit("ID inválido!");

$erro = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // O token só é exigido no POST: o GET apenas mostra a tela de confirmação
    validarCsrf();

    // Guarda os arquivos de imagem para apagar do disco depois do DELETE
    $imgs = $pdo->prepare("SELECT caminho FROM produto_imagens WHERE produto_id = :id");
    $imgs->execute([":id" => $id]);
    $caminhos = $imgs->fetchAll(PDO::FETCH_COLUMN);

    try {
        $server = $pdo->prepare("DELETE FROM produtos WHERE id = :id");
        $server->execute([":id" => $id]);

        foreach ($caminhos as $caminho) {
            // Só apaga arquivos enviados pelo admin (não as imagens de assets/)
            if (str_starts_with($caminho, "uploads/produtos/")) {
                @unlink(__DIR__ . "/../" . $caminho);
            }
        }

        header("Location: painelProdutos.php");
        exit;
    } catch (PDOException $ex) {
        error_log($ex->getMessage());
        // 23503 = violação de chave estrangeira (produto já está em algum pedido)
        $erro = $ex->getCode() === "23503"
            ? "Este produto já faz parte de pedidos e não pode ser excluído. Zere o estoque para tirá-lo da loja."
            : "Não foi possível excluir o produto.";
    }
}

$server = $pdo->prepare("SELECT nome FROM produtos WHERE id = :id");
$server->execute([":id" => $id]);
$produto = $server->fetch();

if (!$produto) exit("Produto não encontrado!");

include_once __DIR__ . "/../../include/cabecalho.php"; ?>

<main class="section narrow">
    <h1>Confirmar exclusão</h1>
    <?php if ($erro !== ""): ?>
        <p class="notice"><?= e($erro) ?></p>
    <?php endif; ?>
    <p>Deseja realmente excluir <?= e($produto["nome"]) ?>?</p>
    <form method="post">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
        <button class="btn danger">Confirmar exclusão</button>
    </form>
    <p>
        <a href="painelProdutos.php">Cancelar</a>
    </p>
</main>

<?php include_once __DIR__ . "/../../include/rodape.php"; ?>
