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
