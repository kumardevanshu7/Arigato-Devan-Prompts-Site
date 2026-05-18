<?php
$_POST['prompt_id'] = 1; // Assuming prompt_id 1 exists
$_POST['action'] = 'save';
$_SESSION['user_id'] = 1; // Assuming user 1 exists

require 'save_prompt.php';
?>
