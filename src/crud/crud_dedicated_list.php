<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
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
		Crud::init('select server_id, account_lid, server_hostname, server_status from servers left join accounts on account_id=server_custid', 'servers')
		->set_title('Dedicated List')
		->set_order('server_status', 'asc')
		->enable_labels()
		->set_labels(['server_id' => 'ID','account_lid' => 'Client','server_hostname' =>  'Server Name', 'server_status' => 'Status'])
		->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.buy_server'), 'Order', 'primary', 'shopping-cart', 'Order Server', 'client')
		->add_title_search_button([['server_status','=','active']], 'Active', 'info')
		->add_title_search_button([['server_status','in',['pending','pending-setup','pend-approval']]], 'Pending', 'info')
		->add_title_search_button([['server_status','in',['canceled','expired']]], 'Expired', 'info')
		->add_title_search_button([], 'All', 'info active')
		->disable_delete()
		->disable_edit()
		->add_row_button(($GLOBALS['tf']->ima == 'admin' ? 'none.view_server_order&id=%id%' : 'none.view_server&id=%id%'), 'View Server', 'primary', 'cog')
		->go();
}
