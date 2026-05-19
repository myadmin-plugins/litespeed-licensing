<?php

require_once __DIR__.'/../../../../include/functions.inc.php';
\MyAdmin\App::session()->sessionid = substr(basename($_SERVER['argv'][0], '.php'), 0, 32);
\MyAdmin\App::session()->account_id = 160308;
\MyAdmin\App::session()->appnocache('ima', 'services');
\MyAdmin\App::tf()->ima = 'services';

$response = activate_litespeed('1.2.3.4', 'LSWS', 1);
echo 'Response: ';
var_export($response);
echo "\n";
//deactivate_cpanel('66.45.228.100');
