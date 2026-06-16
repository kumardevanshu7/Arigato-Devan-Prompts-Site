<?php
require_once '../db.php';

// SuperProfile typically sends a POST request with JSON payload
$json_data = file_get_contents('php://input');
$payload = json_decode($json_data, true);

// For logging testing (so we can see what comes in)
file_put_contents('webhook_log.txt', date('[Y-m-d H:i:s] ') . $json_data . PHP_EOL, FILE_APPEND);

$buyer_email = $payload['buyer_email'] ?? '';
$product_id  = $payload['product_id'] ?? 0;
$transaction_id = $payload['transaction_id'] ?? 'SP_'.time();

if ($buyer_email && $product_id) {
    try {
        // Record the purchase using buyer_email directly
        $ins = $pdo->prepare("INSERT INTO store_purchases (buyer_email, product_id, payment_id) VALUES (?, ?, ?)");
        $ins->execute([$buyer_email, $product_id, $transaction_id]);
        
        echo json_encode(["status" => "success", "message" => "Purchase recorded"]);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) { // Integrity constraint violation (Unique ID)
            echo json_encode(["status" => "success", "message" => "Already recorded"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
        }
    }
} else {
    echo json_encode(["status" => "error", "message" => "Missing data"]);
}
?>
