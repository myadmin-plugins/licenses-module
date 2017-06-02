<?php

namespace Detain\MyAdminLicenses;

use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public function __construct() {
	}

	public static function Load(GenericEvent $event) {
		$service = $event->getSubject();
		$service->set_module('licenses')
			->set_enable(function() {
				myadmin_log($module, 'info', "PPP - {$service_name} - {$service_types[$db->Record[$settings['PREFIX'] . '_type']]['services_category']}", __LINE__, __FILE__);
				myadmin_log($module, 'info', str_replace("\n", '', var_export($service_types[$db->Record[$settings['PREFIX'] . '_type']], true)), __LINE__, __FILE__);
				activate_license($db->Record[$settings['PREFIX'] . '_id']);
				$db2->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$db->Record[$settings['PREFIX'] . '_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($module, 'change_status', 'active', $db->Record[$settings['PREFIX'].'_id'], $db->Record[$settings['PREFIX'] . '_custid']);
				$smarty = new TFSmarty;
				$smarty->assign('license_ip', $db->Record[$settings['PREFIX'] . '_ip']);
				$smarty->assign('license_name', $service_name);
				$email = $smarty->fetch('email/admin_email_license_created.tpl');
				//$subject = $smarty->get_template_vars('subject');
				$subject = 'New ' . $service_name . ' License Created ' . $db->Record[$settings['PREFIX'].'_ip'];
				$headers = '';
				$headers .= 'MIME-Version: 1.0' . EMAIL_NEWLINE;
				$headers .= 'Content-type: text/html; charset=UTF-8' . EMAIL_NEWLINE;
				$headers .= 'From: ' . TITLE . ' <' . EMAIL_FROM . '>' . EMAIL_NEWLINE;
				admin_mail($subject, $email, $headers, false, 'admin_email_license_created.tpl');
			})->set_reactivate(function() {
				myadmin_log($module, 'info', "PPP - {$service_name} - {$service_types[$db->Record[$settings['PREFIX'] . '_type']]['services_category']}", __LINE__, __FILE__);
				myadmin_log($module, 'info', str_replace("\n", '', var_export($service_types[$db->Record[$settings['PREFIX'] . '_type']], true)), __LINE__, __FILE__);
				activate_license($db->Record[$settings['PREFIX'] . '_id']);
				$db2->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$db->Record[$settings['PREFIX'] . '_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($module, 'change_status', 'active', $db->Record[$settings['PREFIX'] . '_id'], $db->Record[$settings['PREFIX'] . '_custid']);
				$smarty = new TFSmarty;
				$smarty->assign('license_ip', $db->Record[$settings['PREFIX'] . '_ip']);
				$smarty->assign('license_name', (isset($service_name) ? $service_name : $service_types[$db->Record[$settings['PREFIX'] . '_type']]['services_name']));
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

}
