<?php

namespace Detain\MyAdminLicenses;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminLicenses
 */
class Plugin {

	public static $name = 'Licensing';
	public static $description = 'Allows selling of Licenses.';
	public static $help = '';
	public static $module = 'licenses';
	public static $type = 'module';
	public static $settings = [
		'SERVICE_ID_OFFSET' => 5000,
		'USE_REPEAT_INVOICE' => TRUE,
		'USE_PACKAGES' => TRUE,
		'BILLING_DAYS_OFFSET' => 0,
		'IMGNAME' => 'certificate.png',
		'REPEAT_BILLING_METHOD' => NORMAL_BILLING,
		'DELETE_PENDING_DAYS' => 45,
		'SUSPEND_DAYS' => 9,
		'SUSPEND_WARNING_DAYS' => 7,
		'TITLE' => 'Licensing',
		'EMAIL_FROM' => 'invoice@cpaneldirect.net',
		'TBLNAME' => 'Licenses',
		'TABLE' => 'licenses',
		'PREFIX' => 'license',
		'TITLE_FIELD' => 'license_ip',
		'TITLE_FIELD2' => 'license_hostname',
		'MENUNAME' => 'Licensing'];

	/**
	 * Plugin constructor.
	 */
	public function __construct() {
	}

	/**
	 * @return array
	 */
	public static function getHooks() {
		return [
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
			self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function loadProcessing(GenericEvent $event) {
		$service = $event->getSubject();
		$service->setModule(self::$module)
			->setEnable(function($service) {
				$serviceTypes = run_event('get_service_types', FALSE, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add(self::$module, 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$smarty = new \TFSmarty;
				$smarty->assign('license_ip', $serviceInfo[$settings['PREFIX'].'_ip']);
				$smarty->assign('service_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/license_created.tpl');
				//$subject = $smarty->get_template_vars('subject');
				$subject = 'New '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' License Created '.$serviceInfo[$settings['PREFIX'].'_ip'];
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, FALSE, 'admin/license_created.tpl');
			})->setReactivate(function($service) {
				$serviceTypes = run_event('get_service_types', FALSE, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add(self::$module, 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$smarty = new \TFSmarty;
				$smarty->assign('license_ip', $serviceInfo[$settings['PREFIX'].'_ip']);
				$smarty->assign('service_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/license_reactivated.tpl');
				$subject = $serviceInfo[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Re-Activated';
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8'.EMAIL_NEWLINE;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, FALSE, 'admin/license_reactivated.tpl');
			})->setDisable(function() {
			})->register();
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_licenses', 'Out Of Stock Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES'), ['0', '1'], ['No', 'Yes']);
	}
}
