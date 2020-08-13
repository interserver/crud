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
 * crud_repeat_invoices()
 * @return void
 */
function crud_repeat_invoices()
{
	Crud::init('select * from repeat_invoices')
		->set_title(_('Repeat Invoices'))
		->go();
}
