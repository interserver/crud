<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2016
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_last_logins()
 * @return void
 */
function crud_last_logins() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select access_ip, access_login from access_log where access_ip != '' group by access_ip")
		->set_title("Last Logn Locations")
		->go();
}
