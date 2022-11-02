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
 * crud_vps()
 * @return void
 */
function crud_vps()
{
	Crud::init('select * from vps', 'vps')
        ->set_limit_custid_role('list_all')
		->set_title(_('Virtual Private Servers'))
		->go();
}
