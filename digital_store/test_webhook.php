<?php
// LOCAL TESTING SCRIPT
// This simulates SuperProfile sending a successful payment webhook to your site

if (isset($_POST['simulate'])) {
    $url = 'http://localhost/Arigato%20Development%20Site/digital_store/webhook.php';
    
    $data = [
        'buyer_email' => $_POST['email'],
        'product_id' => $_POST['product_id'],
        'transaction_id' => 'TEST_' . time()
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    $msg = "Webhook response: " . htmlspecialchars($response);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Simulate Purchase</title>
<link rel="stylesheet" href="css/store.css">
<style>body{padding:40px; background:#111; color:#fff;} .box{background:#1a1a1a; padding:30px; border-radius:12px; max-width:400px; margin:auto;} input, button{width:100%; padding:10px; margin-bottom:15px; border-radius:6px; border:none;} button{background:#fff; color:#000; font-weight:bold; cursor:pointer;}</style>
</head>
<body>
<div class="box">
  <h3>Simulate SuperProfile Payment</h3>
  <?php if(isset($msg)) echo "<p style='color:green'>$msg</p>"; ?>
  <form method="POST">
    <label>Your Account Email (must match login):</label>
    <input type="email" name="email" value="devansh.grow@gmail.com" required>
    
    <label>Product ID to unlock:</label>
    <input type="number" name="product_id" value="1" required>
    
    <button type="submit" name="simulate">Simulate Payment & Unlock</button>
  </form>
  <a href="index.php" style="color:#aaa; display:block; text-align:center; margin-top:10px;">Back to Store</a>
</div>
</body>
</html>
