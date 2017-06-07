<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_licenses define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'MyAdmin Licensing Module',
	'description' => 'Allows selling of Licenses.',
	'help' => '',
	'module' => 'licenses',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-licenses-module',
	'repo' => 'https://github.com/detain/myadmin-licenses-module',
	'version' => '1.0.0',
	'type' => 'module',
	'hooks' => [
		'licenses.load_processing' => ['Detain\MyAdminLicenses\Plugin', 'Load'],
		/* 'function.requirements' => ['Detain\MyAdminLicenses\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminLicenses\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminLicenses\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminLicenses\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminLicenses\Plugin', 'Menu'] */
	],
];
