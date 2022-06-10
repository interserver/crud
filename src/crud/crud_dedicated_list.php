<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_dedicated_list()
 * @return void
 */
function crud_dedicated_list()
{
	Crud::init('select server_id, account_lid, server_hostname, server_status from servers left join accounts on account_id=server_custid', 'servers')
		->set_title(_('Dedicated List'))
		->set_order('server_id', 'desc')
		->enable_labels()
		->set_labels(['server_id' => _('ID'),'account_lid' => _('Client'),'server_hostname' =>  _('Server Name'), 'server_status' => _('Status')])
		->add_header_button($GLOBALS['tf']->default_theme == 'adminlte' ? $GLOBALS['tf']->link('index.php', 'choice=none.buy_server') : $GLOBALS['tf']->link('index.php', 'choice=none.order_server'), _('Order'), 'primary', 'shopping-cart', _('Order Server'), 'client')
		->add_title_search_button([['server_status','in',['active','active-billing']]], _('Active'), 'info active')
		->add_title_search_button([['server_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
		->add_title_search_button([['server_status','in',['canceled','expired']]], _('Expired'), 'info')
		->add_title_search_button([], _('All'), 'info')
		->disable_delete()
		->disable_edit()
		->add_row_button(($GLOBALS['tf']->ima == 'admin' ? 'none.view_server_order&id=%id%' : 'none.view_server&id=%id%'), _('View Server'), 'primary', 'cog')
		->go();
}
