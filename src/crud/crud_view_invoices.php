<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_view_invoices()
 * @return void
 */
function crud_view_invoices() {
		Crud::init("select date_format(invoices_date, '%b %e, %Y'), invoices_type, __TITLE_FIELD__ as service, invoices_description,invoices_custid,invoices_amount,invoices_paid,invoices_group,invoices_extra,invoices_module,invoices_due_date,invoices_service,invoices_id from invoices left join __TABLE__ on invoices_service=__PREFIX___id where invoices_module='__MODULE__'")
		->set_title('View Invoices List')
		->go();
}
