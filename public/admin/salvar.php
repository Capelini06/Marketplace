<?php

session_start();
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";

exigirAdmin();
validarCsrf();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: painelProdutos.php");
    exit;
}

$id = (int) ($_POST["id"] ?? 0);
$nome = trim($_POST["nome"] ?? "");
$preco = filter_input(INPUT_POST, "preco", FILTER_VALIDATE_FLOAT);
$estoque = filter_input(INPUT_POST, "estoque", FILTER_VALIDATE_INT);
$estoque_minimo = filter_input(INPUT_POST, "estoque_minimo", FILTER_VALIDATE_INT);
$garantia_meses = filter_input(INPUT_POST, "garantia_meses", FILTER_VALIDATE_INT);
$arquivo = $_FILES['imagem'] ?? null;
$extensao = null;

if ($arquivo && $arquivo['error'] !== UPLOAD_ERR_NO_FILE) {   // imagem é opcional
    if ($arquivo['error'] !== UPLOAD_ERR_OK) {
        exit('Falha no envio da imagem.');
    }
    if ($arquivo['size'] > 2 * 1024 * 1024) {
        exit('A imagem deve ter no máximo 2 MB.');
    }
    $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($arquivo['tmp_name']);
    if (!isset($tipos[$mime])) {
        exit('Envie uma imagem JPG, PNG ou WEBP.');
    }
    $extensao = $tipos[$mime];
}

function gerarSlug(string $texto): string {
    $texto = iconv("UTF-8", "ASCII//TRANSLIT", $texto);
    $texto = strtolower(trim(preg_replace("/[^A-Za-z0-9]+/", "-", $texto), "-"));
    return $texto !== "" ? $texto : "produto";
}

$slug = gerarSlug($nome) . ($id ? "-$id" : "-" . bin2hex(random_bytes(3)));

if (
    $nome === "" || $preco === null ||
    $preco === false || $preco < 0 ||
    $estoque === false || $estoque === null || $estoque < 0||
    $estoque_minimo === false || $estoque_minimo === null || $estoque_minimo < 0||
    $garantia_meses === false || $garantia_meses === null || $garantia_meses < 0
) {
    exit("Dados inválidos!");
}

$produto = [
    ":nome" => $nome,
    ":categoria_id" => ($_POST["categoria_id"] ?? "") !== "" ? (int) $_POST["categoria_id"] : null,
    ":slug" => $slug,
    ":descricao" => trim($_POST["descricao"]),
    ":garantia_meses" => $garantia_meses,
    ":preco" => $preco,
    ":estoque" => $estoque,
    ":estoque_minimo" => $estoque_minimo
];

if ($id) {
    $produto[":id"] = $id;
    $sql = "UPDATE produtos SET nome= :nome, categoria_id= :categoria_id, slug= :slug , descricao= :descricao, garantia_meses= :garantia_meses, preco= :preco, estoque= :estoque, estoque_minimo= :estoque_minimo WHERE id=:id";
} else {
    $sql = "INSERT INTO produtos(nome, categoria_id, slug, descricao, garantia_meses, preco, estoque, estoque_minimo) VALUES (:nome, :categoria_id, :slug, :descricao, :garantia_meses, :preco, :estoque, :estoque_minimo) RETURNING id";
}
try{
    $banco = $pdo->prepare($sql);
    $banco->execute($produto);

    // Produto novo: pega o id gerado pelo RETURNING id. Edição: usa o id do formulário.
    $produtoId = $id ?: (int) $banco->fetchColumn();

    if ($extensao) {
        $pasta = __DIR__ . '/../uploads/produtos';
        if (!is_dir($pasta)) {
            mkdir($pasta, 0755, true);
        }
        $caminho = 'uploads/produtos/' . bin2hex(random_bytes(16)) . '.' . $extensao;

        if (!move_uploaded_file($arquivo['tmp_name'], __DIR__ . '/../' . $caminho)) {
            exit('Não foi possível salvar a imagem.');
        }

        $img = $pdo->prepare("INSERT INTO produto_imagens (produto_id, caminho, ordem) VALUES (:pid, :caminho, 0)");
        $img->execute([':pid' => $produtoId, ':caminho' => $caminho]);
    }
} catch(PDOException $erro){
    error_log($erro);
    // Temporário, para depuração: mostra o erro real. Remova antes de entregar.
    exit("Falha ao atualizar o banco de dados: " . e($erro->getMessage()));
}

header("Location: painelProdutos.php");
exit;