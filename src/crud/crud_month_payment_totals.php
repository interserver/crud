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
 * crud_month_payment_totals()
 * @return false|void
 */
function crud_month_payment_totals()
{
    // admin only: company-wide payment totals per module (a payment statistics page). the
    // tenant filter could not apply to this grouped query (it was appended after the group by,
    // where it filtered nothing), so any logged in client saw every customer's payments
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('select invoices_module as module, sum(invoices_amount) as total_paid from invoices where year(invoices.invoices_date) = year(now()) AND month(invoices.invoices_date) = month(now()) AND invoices.invoices_type >= 10 group by invoices_module')
        ->set_title(_('Payments This Month'))
        ->disable_delete()
        ->disable_edit()
        ->go();
}
