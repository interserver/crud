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
 * crud_last_logins()
 * @return void
 */
function crud_last_logins()
{
	add_output(alert('TODO', 'Get Client Side Working Right'));
	Crud::init("select access_ip, access_login from access_log where access_ip != '' group by access_ip")
		->set_title(_('Last Logn Locations'))
		->disable_delete()
		->disable_edit()
		->go();
}
