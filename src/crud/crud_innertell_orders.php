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
 * crud_innertell_orders()
 * @return void
 */
function crud_innertell_orders()
{
	Crud::init('select server_id, username, server_hostname, server_status from servers', 'servers')
		->set_title('Dedicated Server Orders')
		->go();
}
