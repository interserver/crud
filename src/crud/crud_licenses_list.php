<?php
/**
 * Licenses List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Licenses
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the License's available to your current session
 *
 * @return void
 */
function crud_licenses_list() {
	//add_output(alert('TODO', 'Get the Paid check working with pagination updates'));
	$module = 'licenses';
	$settings = \get_module_settings($module);
	page_title($settings['TITLE'].' List');
		Crud::init("select {$settings['PREFIX']}_id, {$settings['PREFIX']}_hostname, {$settings['PREFIX']}_ip, services_name, {$settings['PREFIX']}_cost, {$settings['PREFIX']}_status, invoices_paid, invoices_date from {$settings['TABLE']} left join invoices on invoices_extra={$settings['PREFIX']}_invoice and invoices_id=(select max(invoices_id) from invoices where invoices_type=1 and  invoices_extra={$settings['PREFIX']}_invoice) left join services on services_id={$settings['PREFIX']}_type", $module)
		->set_order($settings['PREFIX'].'_status', 'asc')
		->set_title($settings['TITLE'].' List')
		->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.buy_'.$settings['PREFIX'].'3'), 'Order', 'primary', 'shopping-cart', 'Order '.$settings['TITLE'], 'client')
		->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], 'Active', 'info active')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info')
		->add_filter('invoices_paid', ['1' => '<i class="fa fa-fw fa-check"></i>', '2' => '<i class="fa fa-fw fa-times"></i>'], 'simple')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=%id%', 'View '.$settings['TITLE'], 'primary', 'cog')
		->go();
}
