<?php 
include "include/cabecalho.php"; 
require_once __DIR__ . "/include/funcoes.php";
?>


<main class="section">
    <?php if (isset($_SESSION["usuario_id"])): ?>
        <form action="auth/logout.php" method="post">
            <input type="hidden" name="csrf" value="<?= csrfToken()?>">
            <button type="submit">DESLOGAR</button>
        </form>
    <?php else: ?>
        <h3>Nenhuma conta conectada</h3>
        <a class="button" href="login.php">Entrar</a>
        <a href="cadastro.php">Criar conta</a>
    <?php endif ?>
</main>

<?php include "include/rodape.php"; ?>