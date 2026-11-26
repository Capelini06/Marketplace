<?php
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";
require_once __DIR__ . '/InfinitePay.php';

header('Content-Type: application/json');

function responder(int $status, bool $ok, ?string $msg = null): never
{
    http_response_code($status);
    exit(json_encode(['success' => $ok, 'message' => $msg]));
}

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

// Log para depuração: vai para o log de erros do PHP (não fica acessível pela web)
error_log('[InfinitePay webhook] ' . $raw);

if (!$data || empty($data['order_nsu']) || empty($data['transaction_nsu']) || empty($data['invoice_slug'])) {
    responder(400, false, 'Payload inválido');
}

// O order_nsu enviado no checkout é o id do pedido
$stmt = $pdo->prepare('SELECT id, total, status FROM pedidos WHERE id = ?');
$stmt->execute([(int) $data['order_nsu']]);
$pedido = $stmt->fetch();

if (!$pedido) {
    responder(400, false, 'Pedido não encontrado');
}

// Já processado? Responde 200 (idempotência: o webhook pode chegar repetido)
if ($pedido['status'] === 'pago') {
    responder(200, true);
}

if ($pedido['status'] !== 'aguardando_pagamento') {
    responder(400, false, 'Pedido não está aguardando pagamento');
}

// Não confia no corpo do webhook: confirma o pagamento direto na API da InfinitePay
try {
    $check = (new InfinitePay())->verificarPagamento(
        (string) $pedido['id'],
        (string) $data['transaction_nsu'],
        (string) $data['invoice_slug']
    );
} catch (Throwable $e) {
    error_log('[InfinitePay webhook payment_check] ' . $e->getMessage());
    // 500 faz a InfinitePay tentar enviar o webhook de novo mais tarde
    responder(500, false, 'Falha ao verificar pagamento');
}

if (empty($check['paid'])) {
    responder(400, false, 'Pagamento não confirmado');
}

// amount vem em centavos (valor do pedido; paid_amount pode incluir juros); total está em reais
if ((int) ($check['amount'] ?? 0) < centavos($pedido['total'])) {
    responder(400, false, 'Valor divergente');
}

try {
    marcarPedidoPago($pdo, (int) $pedido['id']);
} catch (Throwable $e) {
    error_log('[InfinitePay webhook update] ' . $e->getMessage());
    responder(500, false, 'Falha ao atualizar pedido');
}

responder(200, true);
