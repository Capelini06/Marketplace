<?php
include "./include/cabecalho.php";
require_once "./include/funcoes.php";
require_once "./config/conexao.php";

$produtos = $pdo->query("SELECT id, nome, preco, descricao FROM produtos WHERE ativo = TRUE ORDER BY id DESC LIMIT 3")->fetchAll();
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
                        VOLTRA / <?= (int) $p["id"] ?>
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
                </a>
            <?php endforeach; ?>

        </div>
    </section>
</main>
<?php include "./include/rodape.php" ?>