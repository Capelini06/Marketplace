<?php
require_once __DIR__ . "/../config/conexao.php";
include __DIR__ . "/../include/cabecalho.php";
require_once __DIR__ . "/../include/funcoes.php";

$produtos = $pdo->query(" SELECT p.id, p.nome, p.preco, p.descricao,
            (SELECT i.caminho FROM produto_imagens i
            WHERE i.produto_id = p.id
            ORDER BY i.ordem, i.id LIMIT 1) AS imagem
    FROM produtos p
    WHERE p.ativo = TRUE
    ORDER BY p.id DESC LIMIT 3")->fetchAll();
?>

<main>
    <section class="hero">
        <div>
            <small>Lançamentos • 2026</small>
            <h1>TECNOLOGIA <br>
                <em>SEM LIMITES</em>
            </h1>
            <p>Smartphones, notebooks, áudio e acessórios com o melhor desempenho para o seu dia a dia.</p>
            <a href="loja.php" class="btn light">
                Ver produtos
            </a>
        </div>
        <b class="hero-art">VOLTRA</b>
    </section>

    <section class="section">
        <small>Os mais procurados</small>
        <h2>Destaques da semana</h2>
        <div class="products">
            <?php foreach ($produtos as $p): ?>
                <a href="produtos.php?id=<?= (int) $p["id"] ?>" class="product">
                    <div class="visual">
                        <?php if (!empty($p["imagem"])): ?>
                            <img src="<?= e($p["imagem"]) ?>" alt="<?= e($p["nome"]) ?>">
                        <?php else: ?>
                            VOLTRA / <?= (int) $p["id"] ?>
                        <?php endif; ?>
                    </div>
                    <h3>
                        <?= e($p["nome"]) ?>
                    </h3>
                    <p>
                        <?= e($p["descricao"]) ?>
                    </p>
                    <b>
                        R$ <?= number_format((float) $p["preco"], 2, ",", ".") ?>
                    </b>
                    <form action="acaoCarrinho.php" method="post">
                        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">
                        <input type="hidden" name="acao" value="adicionar">
                        <input type="hidden" name="id" value="<?= (int) $p["id"] ?>">
                        <button type="submit" class="product__btn">Adicionar ao carrinho</button>
                    </form>
                </a>
            <?php endforeach; ?>

        </div>
    </section>
</main>
<?php include __DIR__ . "/../include/rodape.php" ?>