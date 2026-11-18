<?php if (session_status() !== PHP_SESSION_ACTIVE) session_start();
require_once __DIR__ . '/funcoes.php';
$prefixo = prefixo();
?>


<!DOCTYPE html>
<html lang="pt-BR">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? "Voltra Tech - Eletrônicos" ?></title>
    <link rel="stylesheet" href="<?= $prefixo ?>assets/css/style.css">
</head>

<body>
    <header class="site-header">
        <a class="logo" href="<?= $prefixo ?>index.php">VOLTRA <span>TECH</span></a>
        <nav>
        <a href="<?= $prefixo ?>index.php">Início</a>
        <a href="<?= $prefixo ?>loja.php">Loja</a>
    <?php if (isset($_SESSION['usuario_id']) && ($_SESSION['tipo'] ?? '') === 'admin'): ?>
        <a href="<?= $prefixo ?>admin/painelProdutos.php">Admin</a>
    <?php endif; ?>
    <?php if (isset($_SESSION['usuario_id'])): ?>
        <a href="<?= $prefixo ?>auth/logout.php">Sair</a>
    <?php else: ?>
        <a href="<?= $prefixo ?>login.php">Entrar</a>
        <a href="<?= $prefixo ?>cadastro.php">Cadastrar-se</a>
    <?php endif; ?>
    </nav>
    </header>