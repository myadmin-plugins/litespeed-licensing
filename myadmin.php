<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_litespeed define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Litespeed Licensing',
	'description' => 'Allows selling of Litespeed Server and VPS License Types.  More info at https://www.netenberg.com/litespeed.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a litespeed license. Allow 10 minutes for activation.',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-litespeed-licensing',
	'repo' => 'https://github.com/detain/myadmin-litespeed-licensing',
	'version' => '1.0.0',
	'type' => 'licenses',
	'hooks' => [
		'licenses.settings' => ['Detain\MyAdminLitespeed\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminLitespeed\Plugin', 'Activate'],
		/* 'function.requirements' => ['Detain\MyAdminLitespeed\Plugin', 'Requirements'],
		'licenses.change_ip' => ['Detain\MyAdminLitespeed\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminLitespeed\Plugin', 'Menu'] */
	],
];