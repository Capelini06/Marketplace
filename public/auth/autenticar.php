<?php
session_start();

require_once __DIR__ . '/../../config/conexao.php';

// O endpoint de autenticação só aceita requisições POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$senha = $_POST['senha'] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $senha === '') {
    header('Location: ../login.php?erro=1');
    exit;
}

// Busca o usuário e o hash da senha armazenado no banco.
$sql = 'SELECT id, nome, senha, email, tipo AS tipo
        FROM usuarios
        WHERE email = :email
        LIMIT 1';

$stmt = $pdo->prepare($sql);
$stmt->execute([':email' => $email]);
$usuario = $stmt->fetch();

// A senha digitada é verificada somente contra o hash salvo no cadastro.
// A mesma mensagem genérica é usada para usuário inexistente e senha errada.
if (!$usuario || !password_verify($senha, $usuario['senha'])) {
    error_log('[LOGIN] Falha de autenticação.');
    header('Location: ../login.php?erro=1');
    exit;
}

// Atualiza o hash se o algoritmo/custo padrão do PHP tiver mudado.
if (password_needs_rehash($usuario['senha'], PASSWORD_DEFAULT)) {
    $up = $pdo->prepare('UPDATE usuarios SET senha = :senha WHERE id = :id');
    $up->execute([
        ':senha' => password_hash($senha, PASSWORD_DEFAULT),
        ':id'    => $usuario['id'],
    ]);
}

// Aceita somente os dois perfis previstos no sistema.
if (!in_array($usuario['tipo'], ['admin', 'cliente'], true)) {
    error_log('[LOGIN] Falha: tipo de usuário inválido para o id ' . (int) $usuario['id']);
    header('Location: ../login.php?erro=1');
    exit;
}

// Gera um novo ID de sessão após autenticação.
session_regenerate_id(true);

// Os dados de autorização vêm do banco, nunca de campos enviados pelo formulário.
$_SESSION['usuario_id'] = (int) $usuario['id'];
$_SESSION['usuario_nome'] = $usuario['nome'];
$_SESSION['tipo'] = $usuario['tipo'];

// Cada perfil recebe seu próprio destino após o login.
if ($usuario['tipo'] === 'admin') {
    header('Location: ../admin/painelProdutos.php');
    exit;
}

header('Location: ../index.php');
exit;
