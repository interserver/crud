<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_view_invoices2()
 * @return void
 */
function crud_view_invoices2() {
		Crud::init('select i1.invoices_id, replace(i1.invoices_description, concat("(Repeat Invoice: ",i1.invoices_extra,") "), "") as invoices_description, i1.invoices_amount, i1.invoices_paid, i1.invoices_custid, i1.invoices_date, i1.invoices_due_date, i1.invoices_module, i1.invoices_service, i2.invoices_type, i2.invoices_description, i2.invoices_date as paid_date from invoices as i1 left join invoices as i2 on i2.invoices_type >= 10 and i2.invoices_module=i1.invoices_module and i2.invoices_extra=i1.invoices_id where i1.invoices_type=1 order by i1.invoices_id desc')
		->set_title('View Invoices List')
		->go();
}
