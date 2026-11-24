<?php 

session_start();

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();

$produtos=$pdo->query(" SELECT p.id, p.nome, p.garantia_meses,p.preco, p.estoque, p.estoque_minimo, p.ativo c.nome AS categoria 
                        FROM produtos p INNER JOIN categorias c ON c.id = p.categoria_id
                        ORDER BY p.id DESC")->fetchAll();

$titulo = "ADMIN | VITTA";
include __DIR__ . "/../../include/cabecalho.php";
?>

<main class="section">
    <h1>Adminstração de Produtos</h1>
    <p><a class="btn" href="formProduto.php">Novo produto</a></p>
    <p><a class="btn" href="formCategoria.php">Nova categoria</a></p>
    <div class="tablewrap">
        <table>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Preço</th>
                <th>Garantia</th>
                <th>Estoque</th>
                <th>Estoque Minimo</th>
                <th>Ações</th>
            </tr>

            <?php foreach($produtos as $p): ?>
                <?php $estoqueBaixo = (int) $p["estoque"] <= (int) $p["estoque_minimo"]; ?>
                <tr class="<?= $estoqueBaixo ? "linha-estoque-baixo" : "" ?>">
                    <td><?= e($p["nome"]) ?></td>
                    <td><?= e($p["categoria"]) ?></td>
                    <td><?= number_format((float) ($p["preco"]), 2, ",", ".") ?></td>
                    <td><?= (int) $p["garantia_meses"] ?> meses</td>
                    <?php if($estoqueBaixo): ?>
                    <td>
                        <span class="estoque-baixo" title="Estoque igual ou abaixo do mínimo">
                            <span class="estoque-baixo-icone" aria-hidden="true">!</span>
                            <?= (int) $p["estoque"] ?>
                            <span class="estoque-baixo-texto">Repor</span>
                        </span>
                    </td>
                    <?php else: ?>
                    <td class="estoque-normal"><?= (int) $p["estoque"] ?></td>
                    <?php endif ?>
                    <td><?= (int) $p["estoque_minimo"] ?></td>
                    <td>
                        <a href="form.php?id=<?= (int) $p["id"] ?>">Editar</a>
                        |
                        <a href="excluir.php?id=<?= (int) $p["id"] ?>">Excluir</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
</main>

<?php include_once __DIR__ . "/../../include/rodape.php"; ?>