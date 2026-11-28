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
 * crud_paypal_transactions()
 * @return false|void
 */
function crud_paypal_transactions()
{
    // admin only: this lists every customer's rows and index.php/ajax.php reach any
    // registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('select * from paypal')
        ->set_limit_custid_role('list_all')
        // paypal's account column is plain custid, which the default /_custid$/ misses: a
        // requested customer (custid) lists and edits that customer's rows, staff without
        // list_all their own, instead of every row (or, with the role limit, none)
        ->set_custid_match('/^custid$/')
        ->set_title(_('Paypal Transactions'))
        ->go();
}
