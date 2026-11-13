<?php

session_start();

require __DIR__ . "/../config/conexao.php";

if($_SERVER["REQUEST_METHOD"] !== "POST"){
    header("Location: ../login.php");
    exit;
}

$email = filter_input(INPUT_POST, "email", FILTER_VALIDATE_EMAIL);
$senha = $_POST["senha"] ?? "";

$stmt = $pdo -> prepare("SELECT id, nome, senha, tipo FROM usuarios WHERE email = :email LIMIT 1");

$stmt->execute([":email" => $email?: ""]);

$usuario = $stmt->fetch();

if (!$usuario || !password_verify($senha, $usuario["senha"])){
    header("Location: ../login.php?erro=1");
    exit;
}

// Regenera ou gera o id da sessão
session_regenerate_id(true);

// Prenche os campos da sessão
$_SESSION["usuario_id"] = (int)$usuario["id"];
$_SESSION["usuario_nome"] = $usuario["nome"];
$_SESSION["tipo"] = $usuario["tipo"];

if($_SESSION["tipo"] === "admin"){
    header("Location: ../admin/telaAdmin.php");
    exit;
} else{
    header("Location: ../index.php");
    exit;
}
