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
 * crud_paypal_history()
 * @return void
 */
function crud_paypal_history()
{
	Crud::init('Get_PayPal_History')
		->set_title(_('Paypal History'))
		->go();
}
