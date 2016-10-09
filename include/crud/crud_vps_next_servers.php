<?php
function crud_vps_next_servers() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_vps_next_servers")
		->set_title("VPS Next Setup Servers")
		->go();
}
