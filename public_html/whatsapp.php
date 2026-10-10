<?php
/**
 * WhatsApp Cloud API helper.
 * Uses Meta WhatsApp Cloud API to send booking notifications automatically.
 */
require_once __DIR__ . '/config.php';

function whatsapp_enabled(): bool {
    return defined('WHATSAPP_CLOUD_ACCESS_TOKEN')
        && defined('WHATSAPP_PHONE_NUMBER_ID')
        && WHATSAPP_CLOUD_ACCESS_TOKEN !== ''
        && WHATSAPP_PHONE_NUMBER_ID !== ''
        && WHATSAPP_PHONE_NUMBER_ID !== 'YOUR_META_PHONE_NUMBER_ID';
}

function whatsapp_normalize(string $number): string {
    return preg_replace('/\D+/', '', $number);
}

function whatsapp_send_template(string $to, string $template, array $parameters = [], string $language = 'en_US'): array {
    if (!whatsapp_enabled()) {
        return ['ok' => false, 'skipped' => true, 'message' => 'WhatsApp Cloud API is not configured.'];
    }

    $to = whatsapp_normalize($to);
    if ($to === '') {
        return ['ok' => false, 'message' => 'Invalid WhatsApp recipient.'];
    }

    $components = [];
    if ($parameters) {
        $components[] = [
            'type' => 'body',
            'parameters' => array_map(static function ($value) {
                return ['type' => 'text', 'text' => (string)$value];
            }, array_values($parameters))
        ];
    }

    $payload = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'template',
        'template' => [
            'name' => $template,
            'language' => ['code' => $language]
        ]
    ];
    if ($components) {
        $payload['template']['components'] = $components;
    }

    $version = defined('WHATSAPP_GRAPH_VERSION') && WHATSAPP_GRAPH_VERSION !== ''
        ? WHATSAPP_GRAPH_VERSION
        : 'v23.0';
    $url = 'https://graph.facebook.com/' . rawurlencode($version) . '/' . rawurlencode(WHATSAPP_PHONE_NUMBER_ID) . '/messages';

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . WHATSAPP_CLOUD_ACCESS_TOKEN,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
    ]);
    $response = curl_exec($ch);
    $curlError = curl_error($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($response === false || $curlError) {
        return ['ok' => false, 'message' => 'WhatsApp API connection failed: ' . ($curlError ?: 'Unknown cURL error')];
    }

    $data = json_decode($response, true);
    if ($httpCode < 200 || $httpCode >= 300 || isset($data['error'])) {
        $error = $data['error']['message'] ?? ('HTTP ' . $httpCode);
        return ['ok' => false, 'message' => 'WhatsApp API error: ' . $error, 'http_code' => $httpCode, 'response' => $data];
    }

    return ['ok' => true, 'message' => 'WhatsApp message accepted by Meta.', 'response' => $data, 'http_code' => $httpCode];
}

function whatsapp_log(PDO $pdo, string $recipient, string $type, array $result, string $bookingCode = ''): void {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS whatsapp_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_code VARCHAR(30) NULL,
            recipient VARCHAR(30) NOT NULL,
            message_type VARCHAR(40) NOT NULL,
            status VARCHAR(20) NOT NULL,
            response_text TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_booking_code (booking_code),
            INDEX idx_recipient (recipient)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $st = $pdo->prepare('INSERT INTO whatsapp_messages(booking_code,recipient,message_type,status,response_text) VALUES(?,?,?,?,?)');
        $st->execute([
            $bookingCode ?: null,
            whatsapp_normalize($recipient),
            $type,
            !empty($result['ok']) ? 'sent' : (!empty($result['skipped']) ? 'skipped' : 'failed'),
            json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        ]);
    } catch (Throwable $e) {
        // Messaging must never make a successful booking fail.
    }
}
