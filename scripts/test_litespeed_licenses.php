#!/usr/bin/php -q
<?php

require_once(__DIR__ . '/../../../include/functions.inc.php');
include_once(__DIR__ . '/../../../include/licenses/LiteSpeed.php');
$webpage = false;
define('VERBOSE_MODE', false);
global $console;

$ls = new LiteSpeed(LITESPEED_USERNAME, LITESPEED_PASSWORD);
print_r($ls->ping());
