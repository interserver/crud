<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
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
        // list only: its edit fataled before; with that fixed it was a raw accounts editor (any
        // customer's account_lid, and a modal save wrote the order count into account_status)
        ->disable_edit()
        ->go();
}
