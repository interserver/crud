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
 * crud_vps_list_free_ips2()
 * @return void
 */
function crud_vps_list_free_ips2() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("get_vps_free_ips", 'vps', 'function')
		->set_title("Free/Available IPs For VPs Servers")
		->go();
}
