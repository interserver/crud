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
 * crud_modernbill_invoice_list()
 * @return void
 */
function crud_modernbill_invoice_list() {
	function_requirements('class.crud');
	crud::init(
		'select client_info.client_email
	 , client_invoice.client_id
	 , client_invoice.invoice_id
	 , client_invoice.invoice_amount
	 , client_invoice.invoice_amount_paid
	 , client_invoice.invoice_date_entered
	 , client_invoice.invoice_date_due
	 , client_invoice.invoice_date_paid
	 , client_invoice.invoice_payment_method
	 , client_invoice.invoice_subtotal FROM
  client_invoice
LEFT OUTER JOIN client_info
ON client_invoice.client_id = client_info.client_id', 'mb')
		->set_title('Modernbill Invoice List')
		->go();
}
