<?php
/**
 * init_purchase.php
 * AJAX endpoint — called when user clicks "Unlock" button on product.php
 * Sets a one-time session token to validate the purchase on success.php
 * Also accepts guest email for non-logged-in users.
 */
session_start();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'msg' => 'Invalid method']);
    exit;
}

$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
$guest_email = isset($_POST['email']) ? strtolower(trim($_POST['email'])) : '';

if (!$product_id) {
    echo json_encode(['ok' => false, 'msg' => 'Missing product']);
    exit;
}

// If user is logged in, use their session email; else use guest email
$email = '';
if (isset($_SESSION['email']) && !empty($_SESSION['email'])) {
    $email = strtolower(trim($_SESSION['email']));
} elseif (!empty($guest_email)) {
    // Basic email validation
    if (!filter_var($guest_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['ok' => false, 'msg' => 'Invalid email']);
        exit;
    }
    $email = $guest_email;
} else {
    echo json_encode(['ok' => false, 'msg' => 'Email required']);
    exit;
}

// Set one-time session token
$_SESSION['pending_pid']   = $product_id;
$_SESSION['pending_email'] = $email;

echo json_encode(['ok' => true]);
exit;
