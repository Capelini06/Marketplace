<?php
require_once __DIR__ . "/../include/funcoes.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit;
}

// Começa a sessão
session_start();

validarCsrf();

// Limpa as variaveis da sessão
$_SESSION = [];

// Apaga o cookie da sessão do navegador
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        "",
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// destroi a sessão do servidor
session_destroy();

//Redireciona para a pagina inicial
header("Location: ../index.php");
exit;
