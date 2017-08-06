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
	Crud::init('view_invoices2', 'default', 'function')
		->set_title('View Invoices List')
                ->disable_delete()
                ->disable_edit()
		->go();
}
