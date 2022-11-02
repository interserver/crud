<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_invoices()
 * @return void
 */
function crud_invoices()
{
	Crud::init('select * from invoices')
        ->set_limit_custid_role('list_all')
		->set_title(_('Invoices'))
		->go();
}
