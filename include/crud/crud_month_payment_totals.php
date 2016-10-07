<?php
function crud_month_payment_totals() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select '__TBLNAME__' as module, sum(invoices.invoices_amount) AS invoices_total FROM
  invoices
WHERE
  year(invoices.invoices_date) = year(now())
  AND month(invoices.invoices_date) = month(now())
  AND invoices.invoices_type >= 10
  AND invoices.invoices_module='__MODULE__'")
		->set_title("Payments This Month")
		->go();
}
