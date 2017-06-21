<?php
/**
 * LiteSpeed Licensing
 * Last Changed: $LastChangedDate: 2017-05-31 04:54:09 -0400 (Wed, 31 May 2017) $
 * @author detain
 * @version $Revision: 24934 $
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * @param string $ip not used
 * @param string $field1 Product type. Available values: “LSWS” or “LSLB”.
 * @param string $field2 What kind of license. Available values: “1”: 1-CPU license, “2”: 2-CPU license,  “4”: 4-CPU license, “8”: 8-CPU license, “V”: VPS license, “U”: Ultra-VPS license (Available LSWS 4.2.2 and above.), If <order_product> is “LSLB”, <order_cpu> is not required.
 * @param string $period Renewal period. Available values: “monthly”, “yearly”, “owned”.
 * @param mixed $payment Payment method. Available values: “credit”: Use account credit. User can utilize “Add funds” function to pre-deposit money, which will show up as account credit.      “creditcard”: Use credit card to pay. The credit card is pre-defined in the account.  If there is available credit in the account, credit will be applied first, even when the payment method is set to “creditcard”.
 * @param mixed $cvv  (optional) Credit card security code. Try not to set this field. Only if your bank requires this (meaning that the transaction will fail without it) should you then supply this field. CVV code is not stored in the system, so if you need to set it, you have to set this field every time. Other information from your credit card will be taken from your user account.
 * @param mixed $promocode  (optional) Promotional code. If you have a pre-assigned promotional code registered to your account, then you can set it here. Promotional codes are exclusive to each client. If your account is entitled to discounts at the invoice level, you do not need a promotional code.
 * @return array array with the output result. see above for description of output.
 * 		array (
 * 			'LiteSpeed_eService' => array (
 * 				'action' => 'Order',
 * 				'license_id' => '36514',
 * 				'license_type' => 'WS_L_1',
 * 				'invoice_id' => '86300',
 * 				'result' => 'incomplete',
 * 				'message' => 'Invoice 86300 not paid. ',
 * 			),
 * 		)
 */
function activate_litespeed($ip = '', $field1, $field2, $period = 'monthly', $payment = 'credit', $cvv = false, $promocode = false) {
	$ls = new LiteSpeed(LITESPEED_USERNAME, LITESPEED_PASSWORD);
	$response = $ls->order($field1, $field2, $period, $payment, $cvv, $promocode);
	request_log('licenses', false, __FUNCTION__, 'litespeed', 'order', array($field1, $field2, $period, $payment, $cvv, $promocode), $response);
	myadmin_log('licenses', 'info', "activate Litespeed ({$ip}, {$field1}, {$field2}, {$period}, {$payment}, {$cvv}, {$promocode}) Response: " . json_encode($response), __LINE__, __FILE__);
	if (isset($response['LiteSpeed_eService']['serial'])) {
		myadmin_log('licenses', 'info', "Good, got LiteSpeed serial {$response['LiteSpeed_eService']['serial']}", __LINE__, __FILE__);
	} else {
		$subject = "Partial or Problematic LiteSpeed Order {$response['LiteSpeed_eService']['license_id']}";
		$body = $subject . '<br>' . nl2br(print_r($response, true));
		admin_mail($subject, $body, false, false, 'admin_email_licenses_error.tpl');
	}
	return $response;
}

/**
 * @param $ip
 */
function deactivate_litespeed($ip) {
	$ls = new LiteSpeed(LITESPEED_USERNAME, LITESPEED_PASSWORD);
	$response = $ls->cancel(false, $ip);
	request_log('licenses', false, __FUNCTION__, 'litespeed', 'cancel', array(false, $ip), $response);
	myadmin_log('licenses', 'info', "Deactivate Litespeed ({$ip}) Resposne: " . json_encode($response), __LINE__, __FILE__);
}
