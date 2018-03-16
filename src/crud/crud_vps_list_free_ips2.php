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
