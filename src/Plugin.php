<?php

namespace Detain\MyAdminLitespeed;

//use Detain\Litespeed\Litespeed;
use Symfony\Component\EventDispatcher\GenericEvent;

class Plugin {

	public static $name = 'Litespeed Licensing';
	public static $description = 'Allows selling of Litespeed Server and VPS License Types.  More info at https://www.litespeedtech.com/';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a litespeed license. Allow 10 minutes for activation.';
	public static $module = 'licenses';
	public static $type = 'service';


	public function __construct() {
	}

	public static function getHooks() {
		return [
			'licenses.settings' => [__CLASS__, 'getSettings'],
			'licenses.activate' => [__CLASS__, 'getActivate'],
			'licenses.deactivate' => [__CLASS__, 'Deactivate'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			'licenses.change_ip' => [__CLASS__, 'ChangeIp'],
			'ui.menu' => [__CLASS__, 'getMenu'],
		];
	}

	public static function getActivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_LITESPEED) {
			myadmin_log('licenses', 'info', 'Litespeed Activation', __LINE__, __FILE__);
			function_requirements('activate_litespeed');
			$response = activate_litespeed($license->get_ip(), $event['field1'], $event['field2']);
			if (isset($response['LiteSpeed_eService']['serial']))
				$license->set_extra($response['LiteSpeed_eService']['serial'])->save();
			$event->stopPropagation();
		}
	}

	public static function Deactivate(GenericEvent $event) {
		$license = $event->getSubject();
		if ($event['category'] == SERVICE_TYPES_LITESPEED) {
			myadmin_log('licenses', 'info', 'Litespeed Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_litespeed');
			deactivate_litespeed($license->get_ip());
			$event->stopPropagation();
		}
	}

	public static function ChangeIp(GenericEvent $event) {
		if ($event['category'] == SERVICE_TYPES_LITESPEED) {
			$license = $event->getSubject();
			$settings = get_module_settings('licenses');
			$litespeed = new \Litespeed(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log('licenses', 'info', "IP Change - (OLD:".$license->get_ip().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $litespeed->editIp($license->get_ip(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log('licenses', 'error', 'Litespeed editIp('.$license->get_ip().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $license->get_ip());
				$license->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		$module = 'licenses';
		if ($GLOBALS['tf']->ima == 'admin') {
			$menu->add_link($module.'api', 'choice=none.litespeed_list', 'whm/createacct.gif', 'List all Litespeed Licenses');
		}
	}

	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_requirement('litespeed_list', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed_list.php');
		$loader->add_requirement('class.LiteSpeed', '/../vendor/detain/litespeed-licensing/src/LiteSpeed.php');
		$loader->add_requirement('deactivate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
		$loader->add_requirement('activate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
	}

	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting('licenses', 'Litespeed', 'litespeed_username', 'Litespeed Username:', 'Litespeed Username', $settings->get_setting('LITESPEED_USERNAME'));
		$settings->add_text_setting('licenses', 'Litespeed', 'litespeed_password', 'Litespeed Password:', 'Litespeed Password', $settings->get_setting('LITESPEED_PASSWORD'));
		$settings->add_dropdown_setting('licenses', 'Litespeed', 'outofstock_licenses_litespeed', 'Out Of Stock LiteSpeed Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_LITESPEED'), array('0', '1'), array('No', 'Yes', ));
	}
}
