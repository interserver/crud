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
 * crud_vps()
 * @return void
 */
function crud_vps()
{
	Crud::init('select * from vps', 'vps')
		->set_title('Virtual Private Servers')
		->go();
}
