<?php 

require "./include/funcoes.php";
require "./config/conexao.php";

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

$stmt = $pdo->prepare("SELECT p.*, c.nome AS categoria 
                        FROM produtos p 
                        INNER JOIN categorias c 
                        ON c.id = p.categoria_id
                        WHERE p.id=:id AND p.ativo = TRUE");

$stmt -> execute([":id" => $id?: 0]);

$produto = $stmt->fetch();

include "./include/cabecalho.php";
?>

<main class="section">
    <?php if(!$produto): ?>
        <h1>Produto não encontrado!</h1>
    <?php else: ?>
        <div class="detail">
            <div class="visual large">
                VOLTRA / <?= (int)$produto["id"] ?>
            </div>
            <div>
                <small>
                    <?= e($produto["categoria"] ?? "VOLTRA") ?>
                </small>

                <h1>
                    <?= e($produto["nome"]) ?>
                </h1>

                <h2>
                    R$ <?= number_format((float) $produto["preco"], 2, ",", ".") ?>
                </h2>

                <p>
                    <?= e($produto["descricao"]) ?>
                </p>

                <ul class="specs">
                    <li>
                        <span>Marca / Modelo</span>
                        <?= e(trim(($produto["marca"] ?? "") . " " . ($produto["modelo"] ?? ""))) ?>
                    </li>
                    <li>
                        <span>Especificações</span>
                        <?= e($produto["especificacoes"]) ?>
                    </li>
                    <?php if (!empty($produto["voltagem"])): ?>
                        <li>
                            <span>Alimentação</span>
                            <?= e($produto["voltagem"]) ?>
                        </li>
                    <?php endif; ?>
                    <li>
                        <span>Em estoque</span>
                        <?= (int)$produto["estoque"] ?> unidades
                    </li>
                    <li>
                        <span>Garantia</span>
                        <?= (int)$produto["garantia_meses"] ?> meses
                    </li>
                </ul>
            </div>
        </div>
        <?php endif ?>
</main>

<?php include "./include/rodape.php"; ?>