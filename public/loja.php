<?php
require __DIR__ . "/../config/conexao.php";
require __DIR__ . "/../include/funcoes.php";

$busca = trim($_GET['busca'] ?? '');

$sql = 'SELECT p.*, c.nome AS categoria,
            (SELECT i.caminho FROM produto_imagens i
            WHERE i.produto_id = p.id
            ORDER BY i.ordem, i.id LIMIT 1) AS imagem FROM produtos p 
        INNER JOIN categorias c ON p.categoria_id = c.id
        WHERE p.estoque > 0 AND p.ativo = TRUE';

if ($busca !== '')
    $sql .= ' AND p.nome ILIKE :busca';
$sql .= ' ORDER BY p.nome';

$stmt = $pdo->prepare($sql);
if ($busca !== '')
    $stmt->bindValue(':busca', '%' . $busca . '%');
$stmt->execute();
$produtos = $stmt->fetchAll();

include __DIR__ . '/../include/cabecalho.php';
?>

<main class="section">
    <small>VOLTRA / COLEÇÃO</small>
    <h1>Encontre seu próximo periférico.</h1>
    <form method="get" class="search">
        <input name="busca" value="<?= e($busca) ?>" placeholder="Buscar produto...">
        <button class="btn">Buscar</button>
    </form>
    <div class="products">
        <?php foreach ($produtos as $p): ?>
            <a class="product" href="produto.php?id=<?= (int) $p['id'] ?>">
                <div class="visual">
                    <?php if (!empty($p["imagem"])): ?>
                        <img src="<?= e($p["imagem"]) ?>" alt="<?= e($p["nome"]) ?>">
                    <?php else: ?>
                        VOLTRA / <?= (int) $p["id"] ?>
                    <?php endif; ?>
                </div>
                <h3>
                    <?= e($p['nome']) ?>
                </h3>
                <b><?= preco($p['preco']) ?></b>
            </a>
        <?php endforeach; ?>
        <?php if (!$produtos): ?>
            <p>Nenhum produto encontrado.</p>
        <?php endif; ?>
    </div>
</main>

<?php include __DIR__ . '/../include/rodape.php' ?>