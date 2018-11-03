<?php

namespace Detain\MyAdminLicenses;

use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminLicenses
 */
class Plugin
{
	public static $name = 'Licensing';
	public static $description = 'Allows selling of Licenses.';
	public static $help = '';
	public static $module = 'licenses';
	public static $type = 'module';
	public static $settings = [
		'SERVICE_ID_OFFSET' => 5000,
		'USE_REPEAT_INVOICE' => true,
		'USE_PACKAGES' => true,
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
	public function __construct()
	{
	}

	/**
	 * @return array
	 */
	public static function getHooks()
	{
		return [
            'api.register' => [__CLASS__, 'apiRegister'],
            'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.load_processing' => [__CLASS__, 'loadProcessing'],
            self::$module.'.settings' => [__CLASS__, 'getSettings']
		];
	}

    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function getRequirements(GenericEvent $event)
    {
        $loader = $event->getSubject();
        $loader->add_requirement('api_get_license_types', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_cancel_license_ip', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_cancel_license', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_buy_license_prepay', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_buy_license', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_change_license_ip', '/../vendor/detain/myadmin-licenses-module/src/api.php');
        $loader->add_requirement('api_change_license_ip_by_id', '/../vendor/detain/myadmin-licenses-module/src/api.php');
    }
    
    /**
     * @param \Symfony\Component\EventDispatcher\GenericEvent $event
     */
    public static function apiRegister(GenericEvent $event)
    {
        /**
         * @var \ServiceHandler $subject
         */
        //$subject = $event->getSubject();
        //api_register_array('license_types', 'complexType', 'array', '', 'SOAP-ENC:Array', [], [['ref' => 'SOAP-ENC:arrayType', 'wsdl:arrayType' => 'license_type[]']], 'license_type');
        api_register_array('license_type', ['services_id' => 'int', 'services_name' => 'string', 'services_cost' => 'float']);
        api_register_array('buy_license_return', ['status' => 'string', 'status_text' => 'string', 'invoice' => 'int', 'cost' => 'float']);
        api_register_array('change_license_ip_return', ['status' => 'string', 'status_text' => 'string']);
        api_register_array('change_license_ip_by_id_return', ['status' => 'string', 'status_text' => 'string']);
        api_register_array('cancel_license_return', ['status' => 'string', 'status_text' => 'string']);
        api_register_array('cancel_license_ip_return', ['status' => 'string', 'status_text' => 'string']);
        api_register('api_get_license_types', [], ['return' => 'license_types'], 'Get a license of the various license types.', false, false);
        api_register('api_cancel_license_ip', ['sid' => 'string', 'ip' => 'string', 'type' => 'int'], ['return' => 'cancel_license_ip_return'], 'Cancel a License by IP and Type.', true, false);
        api_register('api_cancel_license', ['sid' => 'string', 'id' => 'int'], ['return' => 'cancel_license_return'], 'Cancel a License.', true, false);
        api_register('api_buy_license', ['sid' => 'string', 'ip' => 'string', 'type' => 'int', 'coupon' => 'string'], ['return' => 'buy_license_return'], 'Purchase a License.  Returns an invoice ID.', true, false);
        api_register('api_buy_license_prepay', ['sid' => 'string', 'ip' => 'string', 'type' => 'int', 'coupon' => 'string', 'use_prepay' => 'boolean'], ['return' => 'buy_license_return'], 'Purchase a License and optionally uses PrePay.  Will return an error if use_prepay is true not enough PrePay funds are available.', true, false);
        api_register('api_change_license_ip', ['sid' => 'string', 'oldip' => 'string', 'newip' => 'string'], ['return' => 'change_license_ip_return'], 'Change the IP on an active license.', true, false);
        api_register('api_change_license_ip_by_id', ['sid' => 'string', 'id' => 'int', 'newip' => 'string'], ['return' => 'change_license_ip_by_id_return'], 'Change the IP on an active license.', true, false);
    }    

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function loadProcessing(GenericEvent $event)
	{
		/**
		 * @var \ServiceHandler $service
		 */
		$service = $event->getSubject();
		$service->setModule(self::$module)
			->setEnable(function ($service) {
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$smarty = new \TFSmarty;
				$smarty->assign('license_ip', $serviceInfo[$settings['PREFIX'].'_ip']);
				$smarty->assign('service_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/license_created.tpl');
				//$subject = $smarty->get_template_vars('subject');
				$subject = 'New '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' License Created '.$serviceInfo[$settings['PREFIX'].'_ip'];
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.PHP_EOL;
				$headers .= 'Content-type: text/html; charset=UTF-8'.PHP_EOL;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.PHP_EOL;
				admin_mail($subject, $email, $headers, false, 'admin/license_created.tpl');
			})->setReactivate(function ($service) {
				$serviceTypes = run_event('get_service_types', false, self::$module);
				$serviceInfo = $service->getServiceInfo();
				$settings = get_module_settings(self::$module);
				$db = get_module_db(self::$module);
				$db->query("update {$settings['TABLE']} set {$settings['PREFIX']}_status='active' where {$settings['PREFIX']}_id='{$serviceInfo[$settings['PREFIX'].'_id']}'", __LINE__, __FILE__);
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_status', 'active', $serviceInfo[$settings['PREFIX'].'_id'], $serviceInfo[$settings['PREFIX'].'_custid']);
				$smarty = new \TFSmarty;
				$smarty->assign('license_ip', $serviceInfo[$settings['PREFIX'].'_ip']);
				$smarty->assign('service_name', $serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name']);
				$email = $smarty->fetch('email/admin/license_reactivated.tpl');
				$subject = $serviceInfo[$settings['TITLE_FIELD']].' '.$serviceTypes[$serviceInfo[$settings['PREFIX'].'_type']]['services_name'].' '.$settings['TBLNAME'].' Re-Activated';
				$headers = '';
				$headers .= 'MIME-Version: 1.0'.PHP_EOL;
				$headers .= 'Content-type: text/html; charset=UTF-8'.PHP_EOL;
				$headers .= 'From: '.TITLE.' <'.EMAIL_FROM.'>'.PHP_EOL;
				admin_mail($subject, $email, $headers, false, 'admin/license_reactivated.tpl');
			})->setDisable(function () {
			})->register();
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event)
	{
		$settings = $event->getSubject();
		$settings->add_dropdown_setting(self::$module, 'General', 'outofstock_licenses', 'Out Of Stock Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES'), ['0', '1'], ['No', 'Yes']);
	}
}
