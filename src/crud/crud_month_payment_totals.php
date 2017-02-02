<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_month_payment_totals()
 * @return void
 */
function crud_month_payment_totals() {
	function_requirements('class.crud');
	crud::init("select invoices_module as module, sum(invoices_amount) as total_paid from invoices where year(invoices.invoices_date) = year(now()) AND month(invoices.invoices_date) = month(now()) AND invoices.invoices_type >= 10 group by invoices_module")
		->set_title('Payments This Month')
		->disable_delete()
		->disable_edit()
		->go();
}
