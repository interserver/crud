<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_vps_list_free_ips2()
 * @return void
 */
function crud_vps_list_free_ips2() {
		Crud::init('get_vps_free_ips', 'vps', 'function')
		->set_title('Free/Available IPs For VPs Servers')
		->disable_delete()
		->disable_edit()
		->go();
}
