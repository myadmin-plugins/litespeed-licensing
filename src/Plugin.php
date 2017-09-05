<?php

namespace Detain\MyAdminLiteSpeed;

use Detain\LiteSpeed\LiteSpeed;
use Symfony\Component\EventDispatcher\GenericEvent;

/**
 * Class Plugin
 *
 * @package Detain\MyAdminLiteSpeed
 */
class Plugin {

	public static $name = 'LiteSpeed Licensing';
	public static $description = 'Allows selling of LiteSpeed Server and VPS License Types.  More info at https://www.litespeedtech.com/';
	public static $help = 'It provides more than one million end users the ability to quickly install dozens of the leading open source content management systems into their web space.  	Must have a pre-existing cPanel license with cPanelDirect to purchase a litespeed license. Allow 10 minutes for activation.';
	public static $module = 'licenses';
	public static $type = 'service';

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
			self::$module.'.settings' => [__CLASS__, 'getSettings'],
			self::$module.'.activate' => [__CLASS__, 'getActivate'],
			self::$module.'.reactivate' => [__CLASS__, 'getActivate'],
			self::$module.'.deactivate' => [__CLASS__, 'getDeactivate'],
			self::$module.'.deactivate_ip' => [__CLASS__, 'getDeactivate'],
			'function.requirements' => [__CLASS__, 'getRequirements'],
			self::$module.'.change_ip' => [__CLASS__, 'getChangeIp'],
			'ui.menu' => [__CLASS__, 'getMenu']
		];
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getActivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('LITESPEED')) {
			myadmin_log(self::$module, 'info', 'LiteSpeed Activation', __LINE__, __FILE__);
			function_requirements('activate_litespeed');
			$response = activate_litespeed($serviceClass->getIp(), $event['field1'], $event['field2']);
			if (isset($response['LiteSpeed_eService']['serial']))
				$serviceClass->set_extra($response['LiteSpeed_eService']['serial'])->save();
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getDeactivate(GenericEvent $event) {
		$serviceClass = $event->getSubject();
		if ($event['category'] == get_service_define('LITESPEED')) {
			myadmin_log(self::$module, 'info', 'LiteSpeed Deactivation', __LINE__, __FILE__);
			function_requirements('deactivate_litespeed');
			deactivate_litespeed($serviceClass->getIp());
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getChangeIp(GenericEvent $event) {
		if ($event['category'] == get_service_define('LITESPEED')) {
			$serviceClass = $event->getSubject();
			$settings = get_module_settings(self::$module);
			$litespeed = new \Detain\LiteSpeed\LiteSpeed(FANTASTICO_USERNAME, FANTASTICO_PASSWORD);
			myadmin_log(self::$module, 'info', 'IP Change - (OLD:' .$serviceClass->getIp().") (NEW:{$event['newip']})", __LINE__, __FILE__);
			$result = $litespeed->editIp($serviceClass->getIp(), $event['newip']);
			if (isset($result['faultcode'])) {
				myadmin_log(self::$module, 'error', 'LiteSpeed editIp('.$serviceClass->getIp().', '.$event['newip'].') returned Fault '.$result['faultcode'].': '.$result['fault'], __LINE__, __FILE__);
				$event['status'] = 'error';
				$event['status_text'] = 'Error Code '.$result['faultcode'].': '.$result['fault'];
			} else {
				$GLOBALS['tf']->history->add($settings['TABLE'], 'change_ip', $event['newip'], $serviceClass->getIp());
				$serviceClass->set_ip($event['newip'])->save();
				$event['status'] = 'ok';
				$event['status_text'] = 'The IP Address has been changed.';
			}
			$event->stopPropagation();
		}
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getMenu(GenericEvent $event) {
		$menu = $event->getSubject();
		if ($GLOBALS['tf']->ima == 'admin')
			$menu->add_link(self::$module.'api', 'choice=none.litespeed_list', '/images/whm/createacct.gif', 'List all LiteSpeed Licenses');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getRequirements(GenericEvent $event) {
		$loader = $event->getSubject();
		$loader->add_page_requirement('litespeed_list', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed_list.php');
		$loader->add_requirement('class.LiteSpeed', '/../vendor/detain/litespeed-licensing/src/LiteSpeed.php', '\\Detain\\LiteSpeed\\');
		$loader->add_requirement('deactivate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
		$loader->add_requirement('activate_litespeed', '/../vendor/detain/myadmin-litespeed-licensing/src/litespeed.inc.php');
	}

	/**
	 * @param \Symfony\Component\EventDispatcher\GenericEvent $event
	 */
	public static function getSettings(GenericEvent $event) {
		$settings = $event->getSubject();
		$settings->add_text_setting(self::$module, 'LiteSpeed', 'litespeed_username', 'LiteSpeed Username:', 'LiteSpeed Username', $settings->get_setting('LITESPEED_USERNAME'));
		$settings->add_text_setting(self::$module, 'LiteSpeed', 'litespeed_password', 'LiteSpeed Password:', 'LiteSpeed Password', $settings->get_setting('LITESPEED_PASSWORD'));
		$settings->add_dropdown_setting(self::$module, 'LiteSpeed', 'outofstock_licenses_litespeed', 'Out Of Stock LiteSpeed Licenses', 'Enable/Disable Sales Of This Type', $settings->get_setting('OUTOFSTOCK_LICENSES_LITESPEED'), ['0', '1'], ['No', 'Yes']);
	}
}
