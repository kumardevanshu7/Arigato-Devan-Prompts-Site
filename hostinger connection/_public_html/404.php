<?php
http_response_code(404);

$err_code    = '404';
$err_title   = 'Page Nahi Mila';
$err_badge   = 'Error 404 — Not Found';
$err_heading = 'Oops! Page Nahi Mila!';
$err_desc    = 'Lagta hai yeh page apna rasta bhool gaya...<br>Koi baat nahi — wapas chalte hain! <i class="fa-solid fa-rocket"></i>';
$err_bubble  = 'Yaar... yeh page toh ghoom gaya!';

require __DIR__ . '/includes/error_page_shell.php';
