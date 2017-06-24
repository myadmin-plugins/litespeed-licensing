<?php

require_once(__DIR__ . '/../../../include/functions.inc.php');
include_once(__DIR__ . '/../../../include/licenses/LiteSpeed.php');
$webpage = FALSE;
define('VERBOSE_MODE', FALSE);
global $console;

$ls = new LiteSpeed(LITESPEED_USERNAME, LITESPEED_PASSWORD);
print_r($ls->ping());
