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
 * crud_view_invoices2()
 * @return void
 */
function crud_view_invoices2() {
	function_requirements('class.crud');
	crud::init('select * from invoices')
		->set_title('View Invoices List')
		->go();
}
