<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_dedicated_list()
 * @return void
 */
function crud_dedicated_list() {
		Crud::init('select server_id, server_username, server_hostname, server_status from servers', 'servers')
		->set_title('Dedicated List')
		->enable_labels()
		->set_labels(['server_id' => 'ID','server_username' => 'Client','server_hostname' =>  'Server Name', 'server_status' => 'Status'], true)
		->add_title_search_button([['server_status','=','active']], 'Active', 'info')
		->add_title_search_button([['server_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([['server_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info active')
		->disable_delete()
		->disable_edit()
		->add_row_button(($GLOBALS['tf']->ima == 'admin' ? 'none.view_server_order&id=%id%' : 'none.view_dedicated_server&id=%id%'), 'View Server', 'primary', 'cog')
		->go();
}
