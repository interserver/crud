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
 * crud_vps_list_free_ips()
 * @return void
 */
function crud_vps_list_free_ips()
{
	Crud::init('get_vps_free_ips', 'vps', 'function')
		->set_title(_('Available IPs For VPS Servers'))
		->disable_delete()
		->disable_edit()
		->go();
}
