<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2016
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_invoices()
 * @return void
 */
function crud_invoices() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from invoices")
		->set_title("Invoices")
		->go();
}
