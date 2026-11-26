<?php
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";
require_once __DIR__ . '/InfinitePay.php';

// A InfinitePay manda estes dados na URL ao voltar do pagamento
$orderNsu       = (string) ($_GET['order_nsu'] ?? '');
$transactionNsu = (string) ($_GET['transaction_nsu'] ?? '');
$slug           = (string) ($_GET['slug'] ?? '');
$pago = false;

if ($orderNsu !== '' && $transactionNsu !== '' && $slug !== '') {
    try {
        $pago = confirmarPagamento($pdo, $orderNsu, $transactionNsu, $slug);
    } catch (Throwable $ex) {
        error_log('Retorno: ' . $ex->getMessage());
    }

    // O pedido já guarda os itens: se ele é deste usuário, o carrinho pode ser limpo
    if (isset($_SESSION['usuario_id'])) {
        $stmt = $pdo->prepare('SELECT 1 FROM pedidos WHERE id = ? AND usuario_id = ?');
        $stmt->execute([(int) $orderNsu, (int) $_SESSION['usuario_id']]);
        if ($stmt->fetchColumn()) {
            limparCarrinho();
        }
    }
}

$titulo = 'Pedido | VOLTRA';
include __DIR__ . '/../../include/cabecalho.php';
?>

<main class="section narrow">
    <?php if ($pago): ?>
        <h1>Pagamento confirmado!</h1>
        <p>Recebemos seu pedido e ele já está sendo separado.</p>
    <?php else: ?>
        <h1>Pedido recebido</h1>
        <p>Ainda estamos aguardando a confirmação do pagamento.</p>
    <?php endif; ?>
    <p><a href="../index.php">Voltar à loja</a></p>
</main>

<?php include __DIR__ . '/../../include/rodape.php'; ?>
