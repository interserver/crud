<?php
function crud_vps_list_free_ips2() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_vps_free_ips")
		->set_title("Free/Available IPs For VPs Servers")
		->go();
}
