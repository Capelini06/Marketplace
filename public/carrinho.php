<?php

include_once __DIR__ . "/../include/cabecalho.php";
include_once __DIR__ . "/../include/funcoes.php";
include_once __DIR__ . "/../config/conexao.php";

$itens = itensCarrinho($pdo);
$total = array_sum(array_column($itens, "subtotal"));

?>

    <main class="container">
        <h1>Seu carrinho</h1>

        <?php if (!$itens): ?>
            <p>Seu carrinho está vazio. <a href="index.php">Ver produtos</a></p>
        <?php else: ?>
            <table class="cart">
                <thead>
                    <tr>
                        <th>Produto</th>
                        <th>Preço</th>
                        <th>Qtd</th>
                        <th>Subtotal</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itens as $item): ?>
                        <tr>
                            <td><?= e($item['nome']) ?></td>
                            <td><?= preco($item['preco']) ?></td>
                            <td>
                                <form action="acaoCarrinho.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="acao" value="atualizar">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <input type="number" name="qtd" value="<?= $item['qtd'] ?>" min="0" max="99">
                                    <button type="submit">Atualizar</button>
                                </form>
                            </td>
                            <td><?= preco($item['subtotal']) ?></td>
                            <td>
                                <form action="acaoCarrinho.php" method="post">
                                    <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                    <button type="submit">Remover</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <p class="cart__total">Total: <b><?= preco($total) ?></b></p>

            <div class="cart__actions">
                <a href="index.php">Continuar comprando</a>
                <a href="#" class="btn">Finalizar compra</a>
            </div>
        <?php endif; ?>
    </main>
<?php include_once __DIR__ . "/../include/rodape.php"; ?>