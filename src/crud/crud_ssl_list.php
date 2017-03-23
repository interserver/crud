<?php
/**
 * SSL Certificates List
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @subpackage SSL
 */
use \detain\Crud\Crud;

/**
 * Displays a list of all the SSL Certificates's available to your current session
 *
 * @return void
 */
function crud_ssl_list() {
	$module = 'ssl';
	$settings = get_module_settings($module);
	page_title($settings['TITLE'] . ' List');
		Crud::init("select {$settings['PREFIX']}_id, {$settings['PREFIX']}_hostname, services_name, {$settings['PREFIX']}_status, {$settings['PREFIX']}_company from ssl_certs left join services on {$settings['PREFIX']}_type=services_id", $module)
		->set_title($settings['TITLE'] . ' List')
		->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], 'Active', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=%id%', 'View '.$settings['TITLE'], 'primary', 'cog')
		->go();
}
