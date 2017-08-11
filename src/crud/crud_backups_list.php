<?php

/**
 * Backups List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Backups
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the Backup's available to your current session
 *
 * @return void
 */
function crud_backups_list() {
	$module = 'backups';
	$settings = get_module_settings($module);
	page_title($settings['TITLE'].' List');
	Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, {$settings['PREFIX']}_name, {$settings['PREFIX']}_cost, {$settings['PREFIX']}_username, {$settings['PREFIX']}_status, services_name from {$settings['TABLE']} left join {$settings['PREFIX']}_masters on {$settings['TABLE']}.{$settings['PREFIX']}_server={$settings['PREFIX']}_masters.{$settings['PREFIX']}_id left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
		->set_order($settings['PREFIX'].'_status', 'asc')
		->set_title($settings['TITLE'].' List')
		->enable_labels()
		->set_labels([$settings['PREFIX'].'_id' => 'ID',$settings['PREFIX'].'_username' => 'Username', $settings['PREFIX'].'_cost' => 'Cost', $settings['PREFIX'].'_status' => 'Status', $settings['PREFIX'].'_name' => 'Server', 'services_name' => 'Package'])
		->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], 'Active', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info active')
		->disable_delete()
		->disable_edit()
		//->enable_fluid_container()
		->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=%id%', 'View '.$settings['TITLE'], 'primary', 'cog')
		->go();
}
