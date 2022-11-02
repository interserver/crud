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
 * crud_customers()
 * @return void
 */
function crud_customers()
{
	Crud::init("select account_id,account_lid,account_status, count(__PREFIX___custid) as order_count from accounts left join __TABLE__ on __PREFIX___custid=account_id where account_ima != 'admin' group by account_id")
        ->set_limit_custid_role('list_all')
		->set_title(_('Customers'))
		->go();
}
