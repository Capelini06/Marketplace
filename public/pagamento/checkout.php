<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";
require_once __DIR__ . '/InfinitePay.php';

// O pedido precisa de um usuario_id (NOT NULL no schema)
exigirLogin();

$itens = itensCarrinho($pdo);
if (!$itens) {
    header('Location: ../carrinho.php');
    exit;
}

$total = array_sum(array_column($itens, 'subtotal'));
$usuarioId = (int) $_SESSION['usuario_id'];

$stmt = $pdo->prepare('SELECT nome, email, telefone FROM usuarios WHERE id = ?');
$stmt->execute([$usuarioId]);
$usuario = $stmt->fetch();

if (!$usuario) {
    header('Location: ../auth/logout.php');
    exit;
}

$erros = [];
$dados = [
    'telefone'    => $usuario['telefone'] ?? '',
    'cep'         => '',
    'rua'         => '',
    'numero'      => '',
    'complemento' => '',
    'bairro'      => '',
    'cidade'      => '',
    'uf'          => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validarCsrf();

    foreach ($dados as $campo => $_) {
        $dados[$campo] = trim($_POST[$campo] ?? '');
    }
    $dados['uf'] = strtoupper($dados['uf']);

    $fone = preg_replace('/\D/', '', $dados['telefone']);
    $cep  = preg_replace('/\D/', '', $dados['cep']);

    if (strlen($fone) < 10 || strlen($fone) > 11) $erros[] = 'Telefone inválido (inclua o DDD).';
    if (strlen($cep) !== 8) $erros[] = 'CEP inválido.';
    if ($dados['rua'] === '') $erros[] = 'Informe a rua.';
    if ($dados['numero'] === '') $erros[] = 'Informe o número.';
    if ($dados['bairro'] === '') $erros[] = 'Informe o bairro.';
    if ($dados['cidade'] === '') $erros[] = 'Informe a cidade.';
    if (!preg_match('/^[A-Z]{2}$/', $dados['uf'])) $erros[] = 'UF inválida.';

    if (!$erros) {
        $endereco = $dados['rua'] . ', ' . $dados['numero']
            . ($dados['complemento'] !== '' ? ' - ' . $dados['complemento'] : '')
            . ' - ' . $dados['bairro'] . ', ' . $dados['cidade'] . '/' . $dados['uf'];
        $endereco = mb_substr($endereco, 0, 400);

        $pedidoId = null;

        try {
            // 1. Grava o pedido e os itens no banco
            $pdo->beginTransaction();

            $ins = $pdo->prepare(
                "INSERT INTO pedidos (usuario_id, subtotal, frete, desconto, total, cep, endereco_completo)
                VALUES (?, ?, 0, 0, ?, ?, ?)
                RETURNING id"
            );
            $ins->execute([$usuarioId, $total, $total, $cep, $endereco]);
            $pedidoId = (int) $ins->fetchColumn();

            $stmt = $pdo->prepare(
                "INSERT INTO itens_pedido (pedido_id, produto_id, nome_produto, preco_unitario, quantidade)
                VALUES (?, ?, ?, ?, ?)"
            );
            foreach ($itens as $i) {
                $stmt->execute([$pedidoId, $i['id'], $i['nome'], $i['preco'], $i['qtd']]);
            }

            // Guarda o telefone no cadastro para as próximas compras
            $pdo->prepare('UPDATE usuarios SET telefone = ? WHERE id = ?')
                ->execute([$fone, $usuarioId]);

            $pdo->commit();

            // 2. Gera o link de pagamento na InfinitePay (preços em centavos)
            $url = (new InfinitePay())->criarLink(
                (string) $pedidoId,
                array_map(fn($i) => [
                    'description' => $i['nome'],
                    'quantity'    => $i['qtd'],
                    'price'       => centavos($i['preco']),
                ], $itens),
                BASE_URL . '/pagamento/obrigado.php',
                BASE_URL . '/pagamento/webhook.php',
                [
                    'name'         => $usuario['nome'],
                    'email'        => $usuario['email'],
                    'phone_number' => '+55' . $fone,
                ]
            );

            // 3. Manda o cliente pagar. O carrinho só é limpo na volta (obrigado.php).
            header('Location: ' . $url, true, 303);
            exit;
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            } elseif ($pedidoId) {
                // Pedido já gravado, mas o link falhou: cancela para não ficar pendurado
                $pdo->prepare("UPDATE pedidos SET status = 'cancelado' WHERE id = ?")
                    ->execute([$pedidoId]);
            }
            error_log('Checkout: ' . $ex->getMessage());
            $erros[] = 'Não foi possível iniciar o pagamento. Tente novamente.';
        }
    }
}

$titulo = 'Finalizar compra | VOLTRA';
include __DIR__ . '/../../include/cabecalho.php';
?>

<main class="section narrow">
    <h1>Finalizar compra</h1>

    <?php foreach ($erros as $erro): ?>
        <p class="notice"><?= e($erro) ?></p>
    <?php endforeach; ?>

    <section class="checkout__resumo">
        <?php foreach ($itens as $i): ?>
            <p><?= (int) $i['qtd'] ?>x <?= e($i['nome']) ?> — <?= preco($i['subtotal']) ?></p>
        <?php endforeach; ?>
        <p><b>Total: <?= preco($total) ?></b></p>
    </section>

    <form method="post" class="adminform">
        <input type="hidden" name="csrf" value="<?= csrfToken() ?>">

        <p>Comprando como <b><?= e($usuario['nome']) ?></b> (<?= e($usuario['email']) ?>)</p>

        <label for="telefone">Telefone</label>
        <input type="text" id="telefone" name="telefone" value="<?= e($dados['telefone']) ?>" placeholder="(41) 99999-9999" required>

        <label for="cep">CEP</label>
        <input type="text" id="cep" name="cep" value="<?= e($dados['cep']) ?>" placeholder="00000-000" maxlength="9" required>

        <label for="rua">Rua</label>
        <input type="text" id="rua" name="rua" value="<?= e($dados['rua']) ?>" maxlength="150" required>

        <label for="numero">Número</label>
        <input type="text" id="numero" name="numero" value="<?= e($dados['numero']) ?>" maxlength="10" required>

        <label for="complemento">Complemento</label>
        <input type="text" id="complemento" name="complemento" value="<?= e($dados['complemento']) ?>" maxlength="100">

        <label for="bairro">Bairro</label>
        <input type="text" id="bairro" name="bairro" value="<?= e($dados['bairro']) ?>" maxlength="100" required>

        <label for="cidade">Cidade</label>
        <input type="text" id="cidade" name="cidade" value="<?= e($dados['cidade']) ?>" maxlength="100" required>

        <label for="uf">UF</label>
        <input type="text" id="uf" name="uf" value="<?= e($dados['uf']) ?>" maxlength="2" placeholder="PR" required>

        <button type="submit" class="btn">Ir para o pagamento</button>
    </form>

    <p><a href="../carrinho.php">Voltar ao carrinho</a></p>
</main>

<?php include __DIR__ . '/../../include/rodape.php'; ?>
