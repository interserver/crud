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
 * crud_view_invoices()
 * @return void
 */
function crud_view_invoices() {
	function_requirements('class.crud');
	crud::init("select date_format(invoices_date, '%b %e, %Y'), invoices_type, __TITLE_FIELD__ as service, invoices_description,invoices_custid,invoices_amount,invoices_paid,invoices_group,invoices_extra,invoices_module,invoices_due_date,invoices_service,invoices_id from invoices left join __TABLE__ on invoices_service=__PREFIX___id where invoices_module='__MODULE__'")
		->set_title('View Invoices List')
		->go();
}
