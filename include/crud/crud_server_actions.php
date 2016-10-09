<?php
function crud_server_actions() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select vps_hostname, history_new_value, history_timestamp from history_log left join vps on vps_id=history_type where history_section='vpsqueueold' and vps_id is not null")
		->set_title("Recent Server Commands")
		->go();
}
