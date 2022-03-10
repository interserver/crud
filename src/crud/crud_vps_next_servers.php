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
 * crud_vps_next_servers()
 * @return void
 */
function crud_vps_next_servers()
{
	Crud::init('get_vps_next_servers', 'vps', 'function')
		->set_title(_('VPS Next Setup Servers'))
		->disable_delete()
		->disable_edit()
		->go();
}
