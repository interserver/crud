<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_server_billing_stats()
 * @return void
 */
function crud_server_billing_stats()
{
	Crud::init('get_server_billing_stats', 'default', 'function')
		->set_title('Server Billing Stats')
		->go();
}
