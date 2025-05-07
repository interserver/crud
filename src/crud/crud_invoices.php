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
 * crud_invoices()
 * @return void
 */
function crud_invoices()
{
    if ($GLOBALS['tf']->variables->request['type'] == 'check') {
        Crud::init("SELECT ".
            "date_format(invoices_date, '%Y-%m-%d') as date, concat(invoices_currency,' ',invoices_amount) AS invoice_amount, account_lid as customer, invoices_description as description, invoices_module AS module, invoices_service as service ".
            "FROM invoices ".
            "LEFT JOIN accounts ON account_id = invoices_custid ".
            "WHERE invoices_type = 17 AND invoices_date LIKE '2025%'")
            ->set_limit_custid_role('list_all')
            ->enable_fluid_container()
            ->set_title(_('Invoices'))
            ->go();
    } else {
        Crud::init('select * from invoices')
            ->set_limit_custid_role('list_all')
            ->set_title(_('Invoices'))
            ->go();
    }
}
