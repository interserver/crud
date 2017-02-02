<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \detain\Crud\Crud;

/**
 * crud_last_logins()
 * @return void
 */
function crud_last_logins() {
	add_output(alert('TODO', 'Get Client Side Working Right'));
		Crud::init("select access_ip, access_login from access_log where access_ip != '' group by access_ip")
		->set_title('Last Logn Locations')
		->disable_delete()
		->disable_edit()
		->go();
}
