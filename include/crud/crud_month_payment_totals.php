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
	alert('TODO', 'Have it work over all modules');
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select '__TBLNAME__' as module, sum(invoices.invoices_amount) AS invoices_total FROM
  invoices
WHERE
  year(invoices.invoices_date) = year(now())
  AND month(invoices.invoices_date) = month(now())
  AND invoices.invoices_type >= 10
  AND invoices.invoices_module='__MODULE__'")
		->set_title('Payments This Month')
		->go();
}
