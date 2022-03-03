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
 * crud_month_payment_totals()
 * @return void
 */
function crud_month_payment_totals()
{
	Crud::init('select invoices_module as module, sum(invoices_amount) as total_paid from invoices where year(invoices.invoices_date) = year(now()) AND month(invoices.invoices_date) = month(now()) AND invoices.invoices_type >= 10 group by invoices_module')
		->set_title(_('Payments This Month'))
		->disable_delete()
		->disable_edit()
		->go();
}
