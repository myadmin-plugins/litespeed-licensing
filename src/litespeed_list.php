<?php
/**
 * LiteSpeed Licensing
 * Last Changed: $LastChangedDate: 2017-05-31 04:54:09 -0400 (Wed, 31 May 2017) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */

/**
 * includes the litespeed.inc.php stuff
 *
 * @return void
 */
function litespeed_list() {
	$settings = get_module_settings('licenses');
	add_output('<br>');
	add_output('<img src="/images/litespeed.gif">');
	$t = new TFTable;
	$t->hide_title();
	$t->add_field($settings['TITLE'].' is now offering LiteSpeed Webserver, the fastest commercially available webserver on the market. LiteSpeed is Apache Compatible: No config file changes, no code changes - just drop it in and get faster PHP, faster web serving, DOS protection, host more websites and much more. Running cPanel? Or DirectAdmin? No problem, LiteSpeed is fully compatible. The same config files apache uses including httpd.conf and .htaccess can be used. Your users will see no difference except drastic speed increases. ', 'l');
	$t->add_row();
	add_output($t->get_table());
	add_output('<br>');
	$t = new TFTable;
	$t->set_title('LiteSpeed Advantages');
	$t->add_field('<ul>
	<li> Up to 9 times faster than Apache
	<li> PHP performance increases 50%
	<li> Best Ruby on Rail performance
	<li> 3 times faster than Apache in SSL
	<li> High performance Perl daemon
	<li> Apache compatible htaccess, mod_rewrite, httpd.conf&nbsp;
	<li> Compatible with cPanel and DirectAdmin
	<li> Built in DOS Protection
	<li> Eliminate downtime and increase speeds
</ul>', 'l');
	$t->add_row();
	add_output($t->get_table());
	add_output('<br>');
	add_output('<center>All '.$settings['TITLE'].' customers can get a 10% discount on LiteSpeed products. Please contact <a href="mailto:'.EMAIL_FROM.'">'.EMAIL_FROM.
		'</a> for more information. For example a Single Core Enterprise '.$settings['TBLNAME'].' is 28.80 / mo leased (normally $32/mo). Or see our special below.</center>');
	add_output('<br><br>');
	$t = new TFTable;
	$t->set_options('width=250');
	$t->set_title('Intel Core2Duo 2.66Ghz');
	$t->add_field('<ul>
	<li>E6750 2.66Ghz 1333Mhz
	<li>2GB DDR2 Memory
	<li>250GB SATA Hard Drive
	<li>2000GB Bandwidth
	<li>5 IPs
	<li>LiteSpeed + cPanel</ul><br>
	  <p align="center" class="style6"><a href="https://www.interserver.net/dedicated.php?dedicated_custom_add=core2duowlitespeedcpanel"><img src="/images/cart_16.gif" alt="Configure" border="0">$160.00 per month!</a><br></p>
	', 'l');
	$t->add_row();
	add_output($t->get_table());
}
