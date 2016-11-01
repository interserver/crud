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

/**
 * crud_vps_next_servers()
 * @return void
 */
function crud_vps_next_servers() {
	add_output(alert('TODO', 'Fix field names'));
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init('get_vps_next_servers', 'vps', 'function')
		->set_title('VPS Next Setup Servers')
		->go();
}
