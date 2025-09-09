<?php
// Script para configurar webhook da BullsPay New
require_once __DIR__ . '/../conexao.php';

// Configurações da API
$webhookApiUrl = 'https://api-gateway.bullspay.com.br/api/webhooks/create';
$publicKey = 'bp_client_kpxNgSrcsPO7mw9C4ymv9RlgmJy1fcun';
$privateKey = 'bp_secret_Twf0HMMBWJd6RX1F6PoFVd2bxvpFhlRLjv2nuoCfWLHxWlladqkrFJ6yHiRpQ7KO';

// URL do webhook (ajuste conforme seu domínio)
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$host = $_SERVER['HTTP_HOST'];
$webhookUrl = $protocol . $host . '/callback/bullspay_new.php';

// Payload para criar webhook
$payload = [
    'url' => $webhookUrl,
    'send_withdraw_event' => false,
    'send_transaction_event' => true
];

echo "Configurando webhook da BullsPay New...\n";
echo "URL do webhook: " . $webhookUrl . "\n";

// Fazer requisição para criar webhook
$ch = curl_init($webhookApiUrl);
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($payload),
    CURLOPT_HTTPHEADER => [
        'Accept: application/json',
        'X-Public-Key: ' . $publicKey,
        'X-Private-Key: ' . $privateKey,
        'Content-Type: application/json'
    ]
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "Código HTTP: " . $httpCode . "\n";
echo "Resposta: " . $response . "\n";

if ($curlError) {
    echo "Erro cURL: " . $curlError . "\n";
}

$responseData = json_decode($response, true);

if ($httpCode === 200 || $httpCode === 201) {
    echo "✅ Webhook configurado com sucesso!\n";
    if (isset($responseData['data']['id'])) {
        echo "ID do webhook: " . $responseData['data']['id'] . "\n";
    }
} else {
    echo "❌ Erro ao configurar webhook\n";
    echo "Detalhes: " . print_r($responseData, true) . "\n";
}
?>