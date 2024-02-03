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
 * crud_paypal_transactions()
 * @return void
 */
function crud_paypal_transactions()
{
    Crud::init('select * from paypal')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Paypal Transactions'))
        ->go();
}
