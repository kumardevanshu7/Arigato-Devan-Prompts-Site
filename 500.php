<?php
http_response_code(500);

$err_code    = '500';
$err_title   = 'Server Error';
$err_badge   = 'Error 500 — Server Error';
$err_heading = 'Arre! Server Thoda Ruk Gaya!';
$err_desc    = 'Kuch technical glitch ho gayi...<br>Thodi der baad try karo ya home pe chalo! <i class="fa-solid fa-screwdriver-wrench"></i>';
$err_bubble  = 'Server ne thoda break le liya hai...';

require __DIR__ . '/includes/error_page_shell.php';
