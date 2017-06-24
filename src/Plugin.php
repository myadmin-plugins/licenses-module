<?php

namespace Detain\MyAdminLicenses;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Licensing Module';
	public static $description = 'Allows selling of Licenses.';
	public static $help = '';
	public static $module = 'licenses';
	public static $type = 'module';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'licenses.load_processing' => [__CLASS__, 'Load'],
			'licenses.settings' => [__CLASS__, 'getSettings'],
		];
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		$service->set_module('licenses')
			->set_enable(function() {
				$db2->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$db->Record[$settings['PREFIX'] . '_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($module, 'change_status', 'active', $db->Record[$settings['PREFIX'].'_id'], $db->Record[$settings['PREFIX'] . '_custid']);
				$smarty = new TFSmarty;
				$smarty->assign('license_ip', $db->Record[$settings['PREFIX'] . '_ip']);
				$smarty->assign('service_name', $service_name);
				$email = $smarty->fetch('email/admin_email_license_created.tpl');
				//$subject = $smarty->get_template_vars('subject');
				$subject = 'New ' . $service_name . ' License Created ' . $db->Record[$settings['PREFIX'].'_ip'];
				$headers = '';
				$headers .= 'MIME-Version: 1.0' . EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8' . EMAIL_NEWLINE;
				$headers .= 'From: ' . TITLE . ' <' . EMAIL_FROM . '>' . EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, false, 'admin_email_license_created.tpl');
			})->set_reactivate(function() {
				myadmin_log($module, 'info', "PPP - {$service_name} - {$serviceTypes[$db->Record[$settings['PREFIX'] . '_type']]['services_category']}", __LINE__, __FILE__);
				myadmin_log($module, 'info', str_replace("\n", '', var_export($serviceTypes[$db->Record[$settings['PREFIX'] . '_type']], true)), __LINE__, __FILE__);
				activate_license($db->Record[$settings['PREFIX'] . '_id']);
				$db2->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$db->Record[$settings['PREFIX'] . '_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($module, 'change_status', 'active', $db->Record[$settings['PREFIX'] . '_id'], $db->Record[$settings['PREFIX'] . '_custid']);
				$smarty = new TFSmarty;
				$smarty->assign('license_ip', $db->Record[$settings['PREFIX'] . '_ip']);
				$smarty->assign('service_name', (isset($service_name) ? $service_name : $serviceTypes[$db->Record[$settings['PREFIX'] . '_type']]['services_name']));
				$email = $smarty->fetch('email/admin_email_license_reactivated.tpl');
				$subject = $db->Record[$settings['TITLE_FIELD']].' '.$service_name.' '.$settings['TBLNAME'].' Re-Activated';
				$headers = '';
				$headers .= 'MIME-Version: 1.0' . EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8' . EMAIL_NEWLINE;
				$headers .= 'From: ' . TITLE . ' <' . EMAIL_FROM . '>' . EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, false, 'admin_email_license_reactivated.tpl');
			})->set_disable(function() {
			})->register();

	}

	public static function getSettings(GenericEvent $event) {
		// will be executed when the licenses.settings event is dispatched
		$settings = $event->getSubject();
		$settings->add_dropdown_setting('licenses', 'General', 'outofstock_licenses', 'Out Of Stock Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES'), array('0', '1'), array('No', 'Yes', ));
	}
}
