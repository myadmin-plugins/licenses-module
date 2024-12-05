<?php
/**
* API License Functions
*
* @author Joe Huss <detain@interserver.net>
* @copyright 2025
* @package MyAdmin
* @category API
*/

/**
* gets a list of the possible license types available and there cost.
*
* @return array ad array of available license types
*/
function api_get_license_types()
{
    $db = get_module_db('licenses');
    $db->query("select services_id, services_name, services_cost from services where services_module='licenses' and services_buyable=1", __LINE__, __FILE__);
    $types = [];
    while ($db->next_record(MYSQL_ASSOC)) {
        //			return $db->Record;
        $types[] = $db->Record;
    }
    return $types;
}

/**
 * Cancels one of your Software Licenses of type $service_type on the given IP $ip
 *
 * @param string $sid  Session ID
 * @param string $ip   IP Address to cancel
 * @param int    $service_type Package ID. use [get_license_types](#get-license-types) to get a list of possible types.
 * @return array
 * @throws \Exception
 */
function api_cancel_license_ip($sid, $ip, $service_type)
{
    $service_type = (int)$service_type;
    myadmin_log('api', 'info', "api_cancel_license_ip('{$sid}', '{$ip}', {$service_type}) called", __LINE__, __FILE__);
    $module = 'licenses';
    $db = get_module_db($module);
    $ip = $db->real_escape($ip);
    $settings = get_module_settings($module);
    $service_types = run_event('get_service_types', false, $module);
    $return = [];
    $return['status'] = '';
    $return['status_text'] = '';
    $GLOBALS['tf']->session->sessionid = $sid;
    if ($GLOBALS['tf']->session->verify()) {
        // Read there account data
        $custid = get_custid($GLOBALS['tf']->session->account_id, $module);
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    if (!validIp($ip, false)) {
        $return['status'] = 'error';
        $return['status_text'] .= "Invalid IP Address\n";
        return $return;
    }
    $query = "select * from {$settings['TABLE']} where {$settings['PREFIX']}_ip='{$ip}' and {$settings['PREFIX']}_custid='{$custid}'";
    $db->query($query, __LINE__, __FILE__);
    if ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid License IP/Type';
    } elseif ($db->num_rows() > 1) {
        $return['status'] = 'error';
        $return['status_text'] = 'Multiple Matches, Specify Type.  ';
        while ($db->next_record(MYSQL_ASSOC)) {
            $return['status_text'] .= "License ID {$db->Record[$settings['PREFIX'] . '_id']} Type {$db->Record[$settings['PREFIX'] . '_type']} ({$service_types[$db->Record[$settings['PREFIX'] . '_type']]['services_name']})  ";
            if ($service_type != '' && $service_type == $db->Record[$settings['PREFIX'] . '_type']) {
                // reused code block from below start
                $id = $db->Record[$settings['PREFIX'] . '_id'];
                function_requirements('cancel_service');
                cancel_service($id, $module);
                $return['status'] = 'ok';
                $return['status_text'] = "License Canceled.\n";
                return $return;
                // reused code block from below end
            }
        }
    } else {
        $db->next_record(MYSQL_ASSOC);
        // this code reused above
        $id = $db->Record[$settings['PREFIX'] . '_id'];
        function_requirements('cancel_service');
        cancel_service($id, $module);
        $return['status'] = 'ok';
        $return['status_text'] = "License Canceled.\n";
    }
    return $return;
}

/**
 * Cancels one of your Software Licenses with id $id
 *
 * @param string $sid Session ID
 * @param int    $id  License Order ID
 * @return array
 * @throws \Exception
 */
function api_cancel_license($sid, $id)
{
    $module = 'licenses';
    $id = (int)$id;
    $db = get_module_db($module);
    $settings = get_module_settings($module);
    $return = [];
    $return['status'] = '';
    $return['status_text'] = '';
    $GLOBALS['tf']->session->sessionid = $sid;
    if ($GLOBALS['tf']->session->verify()) {
        // Read there account data
        $custid = get_custid($GLOBALS['tf']->session->account_id, $module);
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    $query = "select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_custid='{$custid}'";
    $db->query($query, __LINE__, __FILE__);
    if ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid License ID';
        return $return;
    }
    $db->next_record(MYSQL_ASSOC);
    function_requirements('cancel_service');
    cancel_service($id, $module);
    $return['status'] = 'ok';
    $return['status_text'] .= "License Canceled.\n";
    return $return;
}

/**
 * Places an order in our system for a software license of type $service_type on the IP $ip
 *
 * @param string    $sid        the session id
 * @param string    $ip         ip address you wish to license some software on
 * @param int       $service_type       the package id of the license type you want. use [get_license_types](#get-license-types) to get a list of possible types.
 * @param string    $coupon     an optional coupon
 * @param null|bool $use_prepay optional, whether or not to use a prepay, if specified as true will return an error if not enough prepay
 * @return array
 * @throws \Exception
 * @throws \SmartyException
 */
function api_buy_license_prepay($sid, $ip, $service_type, $coupon = '', $use_prepay = null)
{
    return api_buy_license($sid, $ip, $service_type, $coupon, $use_prepay);
}

/**
 * Places an order in our system for a software license of type $service_type on the IP $ip
 *
 * @param string    $sid        the session id
 * @param string    $ip         ip address you wish to license some software on
 * @param int       $service_type       the package id of the license type you want. use [get_license_types](#get-license-types) to get a list of possible types.
 * @param string    $coupon     an optional coupon
 * @param null|bool $use_prepay optional, whether or not to use a prepay, if specified as true will return an error if not enough prepay
 * @return array
 * @throws \Exception
 * @throws \SmartyException
 */
function api_buy_license($sid, $ip, $service_type, $coupon = '', $use_prepay = null)
{
    if (null === $use_prepay) {
        $use_prepay = true;
        $prepay_low_funds_triggers_error = false;
    } else {
        $prepay_low_funds_triggers_error = true;
    }
    $module = 'licenses';
    $settings = get_module_settings($module);
    $service_type = (int)$service_type;
    $db = get_module_db($module);
    $ip = $db->real_escape($ip);
    $coupon = $db->real_escape($coupon);
    $service_types = run_event('get_service_types', false, $module);
    $service_cost = $service_types[$service_type]['services_cost'];
    $service_extra = '';
    $frequency = 1;
    $return = [];
    $return['status'] = '';
    $return['status_text'] = '';
    $return['invoice'] = '';
    $return['cost'] = '';
    $coupon_code = 0;
    $paid = false;
    $int_map = [
        5000 => 5008,
        5001 => 5014,
        5002 => 5009,
        10683 => 10682
    ];
    $GLOBALS['tf']->session->sessionid = $sid;
    if ($GLOBALS['tf']->session->verify()) {
        // Read there account data
        $custid = convert_custid($GLOBALS['tf']->session->account_id, $module);
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    $data = $GLOBALS['tf']->accounts->read($custid);
    $continue = true;
    $ip_owner = get_ip_owner($ip);
    if (!validIp($ip, false)) {
        $return['status'] = 'error';
        $return['status_text'] .= "Invalid IP Address\n";
        $continue = false;
    }
    if ($data['status'] == 'locked') {
        $return['status'] = 'error';
        $return['status_text'] .= "Locked Account.\n";
        $continue = false;
    }
    if (in_array($service_type, array_keys($int_map)) && in_array($ip_owner, get_internal_ip_owners())) {
        $return['status'] = 'error';
        $return['status_text'] .= "Your IP appears to be within the {$ip_owner} Network. Because of this your license should be changed to a {$service_types[$int_map[$service_type]]['services_name']} License.  Please submit the updated order form to place the order.\n";
        $continue = false;
    }
    if ($service_types[$service_type]['services_field2'] != '') {
        $valid_blocks = explode(',', $service_types[$service_type]['services_field2']);
        if (!in_array($ip_owner, $valid_blocks)) {
            $return['status'] = 'error';
            $return['status_text'] .= "This IP does not appear to be a {$service_types[$service_type]['services_field2']} IP\n";
            $continue = false;
        }
    }
    // coupon code
    if ($coupon != '') {
        $couponquery = "select * from coupons where (customer=-1 or customer={$custid}) and (usable != 0) and module='{$module}' and (applies=-1 or find_in_set('{$service_type}', applies) != 0) and (name='{$coupon}')";
        $db->query($couponquery, __LINE__, __FILE__);
        if ($db->num_rows() == 0) {
            $return['status'] = 'error';
            $return['status_text'] .= "Invalid {$module} Coupon '{$coupon}' Specified for type '{$service_type}'\n";
            $continue = false;
        } else {
            $db->next_record(MYSQL_ASSOC);
            $coupon_info = $db->Record;
            $coupon_code = $db->Record['id'];
            $tcost = $service_cost;
            switch ($coupon_info['type']) {
                case 1:
                    $service_cost = round($service_cost * ((1 / 100) * (100 - $coupon_info['amount'])), 2);
                    break;
                case 2:
                    $service_cost = bcsub($service_cost, $coupon_info['amount'], 2);
                    break;
                    // set price
                case 3:
                    $service_cost = $coupon_info['amount'];
                    break;
            }
            $GLOBALS['tf']->history->add('apply_coupon', $ip, $service_cost, $tcost, $custid);
        }
    }
    if ($use_prepay == true) {
        $prepay = get_prepay_related_amount([], $module);
        if ($prepay <= $service_cost && $prepay_low_funds_triggers_error == true) {
            $return['status'] = 'error';
            $return['status_text'] .= "You do not have enough Pre-Pay This (Available for a new license: \${$prepay} vs License Cost {$service_cost})\n";
            $continue = false;
        }
    }
    // previously licensed code
    if ($continue) {
        if ($service_types[$service_type]['services_type'] == get_service_define('CPANEL')) {
            $db->query("SELECT services_id FROM services LEFT JOIN service_categories ON category_id=services_category WHERE services_module = 'licenses' AND category_module = 'licenses' and category_tag = 'cpanel'");
            if ($db->num_rows() > 0) {
                while ($db->next_record(MYSQL_ASSOC)) {
                    $temp_service_ids[] = $db->Record['services_id'];
                }
            }
            $service_id_for_type = implode(',', $temp_service_ids);
            $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_ip='{$ip}' and {$settings['PREFIX']}_type in ({$service_id_for_type})", __LINE__, __FILE__);
            if ($db->num_rows() > 0) {
                while ($db->next_record(MYSQL_ASSOC)) {
                    $license_info = $db->Record;
                    if (($db->Record[$settings['PREFIX'] . '_custid'] == $custid) && in_array($db->Record[$settings['PREFIX'] . '_status'], ['canceled', 'expired'])) {
                        $db->query("select * from repeat_invoices where repeat_invoices_id='{$license_info[$settings['PREFIX'] . '_invoice']}'", __LINE__, __FILE__);
                        $rinvids = [];
                        while ($db->next_record(MYSQL_ASSOC)) {
                            $rinvids[] = $db->Record['repeat_invoices_id'];
                        }
                        if (!in_array($license_info[$settings['PREFIX'] . '_invoice'], $rinvids)) {
                            $rinvids[] = $license_info[$settings['PREFIX'] . '_invoice'];
                        }
                        $db->query("select * from invoices where invoices_service='{$license_info[$settings['PREFIX'] . '_id']}'", __LINE__, __FILE__);
                        $invids = [];
                        while ($db->next_record(MYSQL_ASSOC)) {
                            $invids[] = $db->Record['invoices_id'];
                        }
                        $db->query("delete from {$settings['TABLE']} where {$settings['PREFIX']}_id={$license_info[$settings['PREFIX'] . '_id']}", __LINE__, __FILE__);
                        if (count($rinvids) > 0) {
                            sql_delete_by_id('repeat_invoices', $rinvids, $custid, $module);
                        }
                        if (count($invids) > 0) {
                            sql_delete_by_id('invoices', $invids, $custid, $module);
                        }
                    } else {
                        $return['status'] = 'error';
                        $return['status_text'] .= "That IP is already licensed in our system\n";
                        $continue = false;
                    }
                }
            }
        } else {
            $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_ip='{$ip}' and {$settings['PREFIX']}_type={$service_type}", __LINE__, __FILE__);
        }
        if ($db->num_rows() > 0) {
            $db->next_record(MYSQL_ASSOC);
            if (!in_array($db->Record[$settings['PREFIX'] . '_status'], ['canceled', 'expired'])) {
                $return['status'] = 'error';
                $return['status_text'] .= "That IP is already licensed in our system\n";
                $continue = false;
            }
        }
    }
    $return['cost'] = $service_cost;
    if ($continue) {
        $now = mysql_now();
        $repeat_invoice = new \MyAdmin\Orm\Repeat_Invoice($db);
        $repeat_invoice->setId(null)
            ->setDescription($service_types[$service_type]['services_name'])
            ->setType(1)
            ->setCost($service_cost)
            ->setCustid($custid)
            ->setFrequency($frequency)
            ->setDate($now)
            ->set_group(0)
            ->setService(0)
            ->setModule($module)
            ->setLastDate($now)
            ->setNextDate(mysql_date_add($now, 'INTERVAL '.$frequency.' MONTH'))
            ->save();
        $rid = $repeat_invoice->get_id();
        $invoice = $repeat_invoice->invoice($now, $service_cost, false);
        $iid = $invoice->get_id();
        $db->query(make_insert_query(
            $settings['TABLE'],
            [
            $settings['PREFIX'] . '_id' => null,
            $settings['PREFIX'] . '_type' => $service_type,
            $settings['PREFIX'] . '_order_date' => mysql_now(),
            $settings['PREFIX'] . '_custid' => $custid,
            $settings['PREFIX'] . '_ip' => $ip,
            $settings['PREFIX'] . '_status' => 'pending',
            $settings['PREFIX'] . '_invoice' => $rid,
            $settings['PREFIX'] . '_coupon' => $coupon_code,
            $settings['PREFIX'] . '_extra' => $service_extra,
            $settings['PREFIX'] . '_hostname' => ''
                                                       ]
        ), __LINE__, __FILE__);
        $serviceid = $db->getLastInsertId('licenses', $settings['PREFIX'] . '_id');
        $invoice->set_service($serviceid)->save();
        $repeat_invoice->set_service($serviceid)->save();
        myadmin_log('api', 'info', "Signup - License {$serviceid} For Customer {$custid} Cost {$service_cost} Invoice {$iid} Repeat Invoice {$rid}", __LINE__, __FILE__);
        $smarty = new TFSmarty();
        $smarty->assign('license_ip', $ip);
        $smarty->assign('license_cost', number_format($service_cost, 2));
        function_requirements('get_paypal_link');
        $smarty->assign('paypal_link', get_paypal_link('SERVICElicenses' . $serviceid, $service_cost, $invoice->get_description()));
        $msg = $smarty->fetch('email/client/license_paytoactivate.tpl');
        $subject = 'New Pending License for ' . $ip . ' at cPanelDirect.net';
        (new \MyAdmin\Mail())->multiMail($subject, $msg, $data['account_lid'], 'client/license_paytoactivate.tpl');
        $return['status'] = 'ok';
        $return['status_text'] .= "Order Completed Successfully.\n";
        $return['invoice'] = $iid;
    }
    if ($use_prepay == true) {
        $prepay = get_prepay_related_amount([$iid], $module);
        if ($prepay >= $service_cost) {
            use_prepay_related_amount($iid, $module, $service_cost);
            handle_payment($custid, $service_cost, $iid, 12, $module);
            $return['invoice'] = 0;
            $return['status_text'] .= 'Order Completed and Invoice Paid With PrePay and License Activated';
        }
    }
    $return['status_text'] = trim($return['status_text']);
    return $return;
}

/**
 * changes the ip address a licenses is registered with
 *
 * @param string $sid the session id
 * @param string $oldip the old ip address
 * @param string $newip the new ip address
 * @return array|bool
 */
function api_change_license_ip($sid, $oldip, $newip)
{
    $GLOBALS['tf']->session->sessionid = $sid;
    $module = 'licenses';
    $settings = get_module_settings($module);
    $db = get_module_db($module);
    $oldip = $db->real_escape($oldip);
    if ($GLOBALS['tf']->session->verify()) {
        $custid = $GLOBALS['tf']->session->account_id;
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
        if (!check_auth_limits(false)) {
            myadmin_log('api', 'info', 'This account is currently locked or does not meet the requirements to login.', __LINE__, __FILE__);
            return false;
        }
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    $data = $GLOBALS['tf']->accounts->read($custid);
    if ($data['status'] == 'locked') {
        $return['status'] = 'error';
        $return['status_text'] .= "Locked Account.\n";
        return $return;
    }
    if (!validIp($oldip, false)) {
        $return['status'] = 'error';
        $return['status_text'] .= "Invalid IP Address\n";
        return $return;
    }
    $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_ip='{$oldip}' and {$settings['PREFIX']}_custid='{$custid}'", __LINE__, __FILE__);
    if ($db->num_rows() > 1) {
        $return['status'] = 'error';
        $return['status_text'] .= 'Multiple Licenses On This IP.  Use the website or contact support@interserver.net.';
        return $return;
    } elseif ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'This License either does not exist or it is not owned by you.';
        return $return;
    }
    $db->next_record(MYSQL_ASSOC);
    $id = $db->Record[$settings['PREFIX'].'_id'];
    $return = license_change_ip($id, $newip);
    return $return;
}

/**
 * changes the ip address a licenses is registered with using the given license id
 *
 * @param string $sid the session id
 * @param int $id the old ip address
 * @param string $newip the new ip address
 * @return array|bool
 */
function api_change_license_ip_by_id($sid, $id, $newip)
{
    $id = (int)$id;
    $GLOBALS['tf']->session->sessionid = $sid;
    $module = 'licenses';
    $settings = get_module_settings($module);
    $db = get_module_db($module);
    if ($GLOBALS['tf']->session->verify()) {
        $custid = $GLOBALS['tf']->session->account_id;
        $GLOBALS['tf']->accounts->data = $GLOBALS['tf']->accounts->read($custid);
        $GLOBALS['tf']->ima = $GLOBALS['tf']->session->appsession('ima');
        $GLOBALS['tf']->session->update_dla();
        if (!check_auth_limits(false)) {
            myadmin_log('api', 'info', 'This account is currently locked or does not meet the requirements to login.', __LINE__, __FILE__, $module);
            return false;
        }
    } else {
        $return['status'] = 'error';
        $return['status_text'] = 'Invalid Session ID';
        return $return;
    }
    update_session_log(__FUNCTION__);
    $data = $GLOBALS['tf']->accounts->read($custid);
    if ($data['status'] == 'locked') {
        $return['status'] = 'error';
        $return['status_text'] .= "Locked Account.\n";
        return $return;
    }
    $db->query("select * from {$settings['TABLE']} where {$settings['PREFIX']}_id='{$id}' and {$settings['PREFIX']}_custid='{$custid}'", __LINE__, __FILE__);
    if ($db->num_rows() > 1) {
        $return['status'] = 'error';
        $return['status_text'] .= 'Multiple Licenses On This IP.  Use the website or contact support@interserver.net.';
        return $return;
    } elseif ($db->num_rows() == 0) {
        $return['status'] = 'error';
        $return['status_text'] = 'This License either does not exist or it is not owned by you.';
        return $return;
    }
    $db->next_record(MYSQL_ASSOC);
    $id = $db->Record[$settings['PREFIX'].'_id'];
    $return = license_change_ip($id, $newip);
    return $return;
}
