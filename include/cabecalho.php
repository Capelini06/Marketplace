<?php if(session_status() !== PHP_SESSION_ACTIVE) session_start(); ?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? "Voltra Tech - Eletrônicos" ?></title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
    <header class="site-header">
        <a href="index.php" class="logo">VOLTRA <span>TECH</span></a>
        <nav>
            <a href="index.php">Início</a>
            <a href="loja.php">Loja</a>
            
            <?php if (isset($_SESSION["usuario_id"])): ?>
                <a href="meuPerfil.php">Perfil</a>
            <?php else: ?>
                <a href="login.php">Entrar</a>
                <a href="cadastro.php">Cadastrar</a>
            <?php endif ?>
        </nav>
    </header>
