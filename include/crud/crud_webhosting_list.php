<?php
/**
 * Webhosting List
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @subpackage Webhosting
 */

/**
 * Displays a list of all the Websites's available to your current session
 *
 * @return void
 */
function crud_webhosting_list() {
	$module = 'webhosting';
	$settings = get_module_settings($module);
	page_title($settings['TITLE'] . ' List');
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, {$settings['PREFIX']}_name, {$settings['PREFIX']}_cost, {$settings['PREFIX']}_hostname, {$settings['PREFIX']}_status, services_name, {$settings['PREFIX']}_comment from {$settings['TABLE']} left join {$settings['PREFIX']}_masters on {$settings['PREFIX']}_server={$settings['PREFIX']}_masters.{$settings['PREFIX']}_id left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
		->set_title($settings['TITLE'] . ' List')
		->enable_labels()
		->set_labels([$settings['PREFIX'].'_id' => 'ID',$settings['PREFIX'].'_hostname' => 'Hostname', $settings['PREFIX'].'_cost' => 'Cost', $settings['PREFIX'].'_status' => 'Status', $settings['PREFIX'].'_name' => 'Server', $settings['PREFIX'].'_comment' => 'Comments', 'services_name' => 'Package'], true)
		->add_title_search_button(array(array($settings['PREFIX'].'_status','=','active')), 'Active', 'info')
		->add_title_search_button(array(array($settings['PREFIX'].'_status','in',array('pending','pending-setup','pend-approval'))), 'Pending', 'info')
		->add_title_search_button(array(array($settings['PREFIX'].'_status','in',array('canceled','expired'))), 'Expired', 'info')
		->add_title_search_button(array(), 'All', 'info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=%id%', 'View '.$settings['TITLE'], 'primary', 'cog')
		->go();
}
