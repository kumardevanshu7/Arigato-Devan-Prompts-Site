<?php
http_response_code(500);

$err_code    = '500';
$err_title   = 'Server Error';
$err_badge   = 'Error 500 — Server Error';
$err_heading = 'Internal Server Error';
$err_desc    = 'Something went wrong on our end.<br>Please try again in a moment or head back home! <i class="fa-solid fa-screwdriver-wrench"></i>';
$err_bubble  = 'Our server is taking a quick breath...';

require __DIR__ . '/includes/error_page_shell.php';
