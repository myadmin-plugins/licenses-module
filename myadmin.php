<?php
/* TODO:
 - service type, category, and services  adding
 - dealing with the SERVICE_TYPES_licenses define
 - add way to call/hook into install/uninstall
*/
return [
	'name' => 'Licenses Licensing VPS Addon',
	'description' => 'Allows selling of Licenses Server and VPS License Types.  More info at https://www.netenberg.com/licenses.php',
	'help' => 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a licenses license. Allow 10 minutes for activation.',
	'module' => 'vps',
	'author' => 'detain@interserver.net',
	'home' => 'https://github.com/detain/myadmin-licenses-vps-addon',
	'repo' => 'https://github.com/detain/myadmin-licenses-vps-addon',
	'version' => '1.0.0',
	'type' => 'addon',
	'hooks' => [
		'vps.load_addons' => ['Detain\MyAdminVpsLicenses', 'Load'],
		/* 'function.requirements' => ['Detain\MyAdminVpsLicenses\Plugin', 'Requirements'],
		'licenses.settings' => ['Detain\MyAdminVpsLicenses\Plugin', 'Settings'],
		'licenses.activate' => ['Detain\MyAdminVpsLicenses\Plugin', 'Activate'],
		'licenses.change_ip' => ['Detain\MyAdminVpsLicenses\Plugin', 'ChangeIp'],
		'ui.menu' => ['Detain\MyAdminVpsLicenses\Plugin', 'Menu'] */
	],
];
