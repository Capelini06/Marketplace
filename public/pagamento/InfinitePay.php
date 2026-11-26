<?php
require_once __DIR__ . "/../../config/conexao.php";
require_once __DIR__ . "/../../include/funcoes.php";

class InfinitePay
{
    public function __construct(
        private string $handle = INFINITEPAY_HANDLE,
        private string $baseUrl = INFINITEPAY_API
    ) {}

    // Cria um link de checkout. Os preços dos itens devem vir em CENTAVOS.
    public function criarLink(
        string $orderNsu,
        array $itens,
        string $redirectUrl,
        string $webhookUrl,
        ?array $cliente = null
    ): string {
        $payload = [
            'handle'       => $this->handle,
            'order_nsu'    => $orderNsu,
            'redirect_url' => $redirectUrl,
            'webhook_url'  => $webhookUrl,
            'items'        => array_map(fn($i) => [
                'quantity'    => (int) $i['quantity'],
                'price'       => (int) $i['price'],
                'description' => (string) $i['description'],
            ], $itens),
        ];

        if ($cliente) {
            $payload['customer'] = $cliente;
        }

        $resp = $this->post('/links', $payload);

        if (empty($resp['url'])) {
            throw new RuntimeException('Resposta sem URL: ' . json_encode($resp));
        }
        return $resp['url'];
    }

    // Consulta se um pagamento foi realmente aprovado.
    public function verificarPagamento(
        string $orderNsu,
        string $transactionNsu,
        string $slug
    ): array {
        return $this->post('/payment_check', [
            'handle'          => $this->handle,
            'order_nsu'       => $orderNsu,
            'transaction_nsu' => $transactionNsu,
            'slug'            => $slug,
        ]);
    }

    private function post(string $path, array $body): array
    {
        $ch = curl_init($this->baseUrl . $path);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_POSTFIELDS     => json_encode($body, JSON_UNESCAPED_UNICODE),
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 15,
        ]);

        $raw    = curl_exec($ch);
        $erro   = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        unset($ch);

        if ($raw === false) {
            throw new RuntimeException("Erro de conexão com a InfinitePay: $erro");
        }

        $json = json_decode($raw, true) ?? [];

        if ($status >= 400) {
            $msg = $json['message'] ?? $raw;
            throw new RuntimeException("InfinitePay HTTP $status: $msg");
        }
        return $json;
    }
}

// Confirma o pagamento de um pedido consultando a API e, se aprovado,
// marca o pedido como pago e baixa o estoque.
// O order_nsu enviado à InfinitePay é o id do pedido.
// Retorna true se o pedido está pago.
function confirmarPagamento(
    PDO $pdo,
    string $orderNsu,
    string $transactionNsu,
    string $slug
): bool {
    $stmt = $pdo->prepare('SELECT id, total, status FROM pedidos WHERE id = ?');
    $stmt->execute([(int) $orderNsu]);
    $pedido = $stmt->fetch();

    if (!$pedido) return false;
    if ($pedido['status'] === 'pago') return true; // o webhook pode ter chegado antes
    if ($pedido['status'] !== 'aguardando_pagamento') return false;

    // Não confia nos parâmetros da URL: pergunta para a InfinitePay
    $check = (new InfinitePay())->verificarPagamento((string) $pedido['id'], $transactionNsu, $slug);

    if (empty($check['paid'])) return false;

    // amount vem em centavos; total do pedido está em reais
    if ((int) ($check['amount'] ?? 0) < centavos($pedido['total'])) return false;

    marcarPedidoPago($pdo, (int) $pedido['id']);
    return true;
}
