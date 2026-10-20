<?php
http_response_code(404);

$err_code    = '404';
$err_title   = 'Page Not Found';
$err_badge   = 'Error 404 — Not Found';
$err_heading = 'Oops! Page Not Found';
$err_desc    = 'The page you are looking for does not exist or has been moved.<br>Let us get you back on track! <i class="fa-solid fa-rocket"></i>';
$err_bubble  = 'Looks like this page took a wrong turn!';

require __DIR__ . '/includes/error_page_shell.php';
