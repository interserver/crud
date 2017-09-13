<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_server_actions()
 * @return void
 */
function crud_server_actions() {
		Crud::init("select vps_hostname, history_new_value, history_timestamp from history_log left join vps on vps_id=history_type where history_section='vpsqueueold' and vps_id is not NULL", 'vps')
		->set_title('Recent Server Commands')
		->go();
}
