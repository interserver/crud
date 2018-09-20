<?php
/**
 * Domains List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Domains
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the Domains's available to your current session
 *
 * @return void
 */
function crud_domains_list()
{
	$module = 'domains';
	$settings = \get_module_settings($module);
	page_title($settings['TITLE'].' List');
	Crud::init("select {$settings['PREFIX']}_id,{$settings['PREFIX']}_hostname,{$settings['PREFIX']}_cost,{$settings['PREFIX']}_status from {$settings['TABLE']}", $module)
		->set_order($settings['PREFIX'].'_status', 'asc')
		->set_title($settings['TITLE'].' List')
		->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.buy_new_'.$settings['PREFIX']), 'Order', 'primary', 'shopping-cart', 'Order '.$settings['TITLE'], 'client')
		->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], 'Active', 'info active')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=%id%', 'View '.$settings['TITLE'], 'primary', 'cog')
		->go();
}
