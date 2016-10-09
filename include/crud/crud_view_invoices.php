<?php
function crud_view_invoices() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select date_format(invoices_date, '%b %e, %Y'), invoices_type, __TITLE_FIELD__ as service, invoices_description,invoices_custid,invoices_amount,invoices_paid,invoices_group,invoices_extra,invoices_module,invoices_due_date,invoices_service,invoices_id from invoices left join __TABLE__ on (invoices_service=__PREFIX___id or (invoices_service=0 and invoices_type=1 and (__PREFIX___invoice=invoices_extra or invoices_description like concat('%for __TBLNAME__ ', __PREFIX___id)))) where invoices_module='__MODULE__'")
		->set_title("View Invoices List")
		->go();
}
