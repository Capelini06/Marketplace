<?php

// Prefixo relativo até a raiz de public/ ("../" para páginas em admin/ ou auth/)
function prefixo(): string
{
    $script = str_replace('\\', '/', $_SERVER['PHP_SELF'] ?? '');
    return (str_contains($script, '/admin/') || str_contains($script, '/auth/')) ? '../' : '';
}

function e(?string $valor): string{
    return htmlspecialchars($valor ?? "", ENT_QUOTES, "UTF-8");
}

function exigirLogin(): void
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }

    if (!isset($_SESSION['usuario_id'])) {
        header('Location: ' . prefixo() . 'login.php');
        exit;
    }
}

// Verifica se o usuario que esta acessando é o admin ou não, se não for, vai para o login
function exigirAdmin(): void{
    // Verifica se o estado da sessão é diferente de ativo, se for, se cria uma sessão
    if(session_status() !== PHP_SESSION_ACTIVE) session_start();
    if(!isset(
        $_SESSION["usuario_id"]) 
        ||
        ($_SESSION["tipo"] ?? "")
        !== "admin"){
            header("Location: " . prefixo() . "login.php");
            exit;
        }
}

function exigirCliente(): void
{
    exigirLogin();

    if (($_SESSION['tipo'] ?? '') !== 'cliente') {
        header('Location: ' . prefixo() . 'admin/painelProdutos.php');
        exit;
    }
}
function csrfToken(): string {
    if(empty($_SESSION["csrf"])){
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
    }
    return $_SESSION["csrf"];
}

function validarCsrf():void {
    if(!hash_equals($_SESSION["csrf"] ?? "", $_POST["csrf"] ?? "")){
        http_response_code(403);
        exit("Requisicão inválida");
    }
}