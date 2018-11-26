<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
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
		->set_title(_('Invoices'))
		->go();
}
