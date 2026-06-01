<?php
function sendFCMNotification($title, $body, $url = 'https://arigatodevan.com') {
    $projectId    = 'arigato-devan-prompts';
    $keyFile      = __DIR__ . '/private/fcm-service-account.json';

    if (!file_exists($keyFile)) return false;

    $accessToken = _fcm_getAccessToken($keyFile);
    if (!$accessToken) return false;

    global $pdo;
    try {
        $tokens = $pdo->query("SELECT token FROM fcm_tokens")->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $e) {
        return false;
    }

    if (empty($tokens)) return true;

    $endpoint = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

    foreach ($tokens as $token) {
        $payload = [
            'message' => [
                'token' => $token,
                'notification' => [
                    'title' => $title,
                    'body'  => $body,
                ],
                'webpush' => [
                    'notification' => [
                        'icon'  => 'https://arigatodevan.com/toplogo/logo01.webp',
                        'badge' => 'https://arigatodevan.com/favicon/favicon-32x32.png',
                    ],
                    'fcm_options' => ['link' => $url],
                ],
            ],
        ];

        $ch = curl_init($endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        $response     = curl_exec($ch);
        $responseData = json_decode($response, true);
        curl_close($ch);

        if (isset($responseData['error']['status']) &&
            in_array($responseData['error']['status'], ['NOT_FOUND', 'INVALID_ARGUMENT', 'UNREGISTERED'])) {
            try {
                $pdo->prepare("DELETE FROM fcm_tokens WHERE token = ?")->execute([$token]);
            } catch (Exception $e) {}
        }
    }

    return true;
}

function _fcm_getAccessToken($keyFile) {
    $sa = json_decode(file_get_contents($keyFile), true);
    if (!$sa || empty($sa['private_key']) || empty($sa['client_email'])) return null;

    $now = time();

    $b64 = function($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    };

    $header  = $b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $payload = $b64(json_encode([
        'iss'   => $sa['client_email'],
        'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
        'aud'   => 'https://oauth2.googleapis.com/token',
        'exp'   => $now + 3600,
        'iat'   => $now,
    ]));

    $signInput = $header . '.' . $payload;
    openssl_sign($signInput, $signature, $sa['private_key'], 'SHA256');
    $jwt = $signInput . '.' . $b64($signature);

    $ch = curl_init('https://oauth2.googleapis.com/token');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion'  => $jwt,
    ]));
    $res = json_decode(curl_exec($ch), true);
    curl_close($ch);

    return $res['access_token'] ?? null;
}
?>
