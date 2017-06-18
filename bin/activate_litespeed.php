<?php

require_once(__DIR__ . '/../../../include/functions.inc.php');
$GLOBALS['tf']->session->create(160308, 'services');
$GLOBALS['tf']->session->verify();

$response = activate_litespeed('1.2.3.4', 'LSWS', 1);
echo "Response: ";
var_export($response);
echo "\n";
//deactivate_cpanel('66.45.228.100');

$GLOBALS['tf']->session->destroy();
