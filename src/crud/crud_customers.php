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
 * @return false|void
 */
function crud_customers()
{
    // admin only: it lists customer accounts (id, login email, status). the tenant filter
    // could not apply to this grouped query (it was appended after the group by, where it
    // filtered nothing), so it leaked other customers' accounts to any logged in client
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('view_customer')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init("select account_id,account_lid,account_status, count(__PREFIX___custid) as order_count from accounts left join __TABLE__ on __PREFIX___custid=account_id where account_ima != 'admin' group by account_id")
        ->set_limit_custid_role('list_all')
        ->set_title(_('Customers'))
        // list only: its edit fataled before; with that fixed it was a raw accounts editor (any
        // customer's account_lid, and a modal save wrote the order count into account_status)
        ->disable_edit()
        ->go();
}
