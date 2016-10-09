<?php
function crud_vps_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select vps.vps_id as service_id, vps_masters.vps_name, vps_cost, vps_hostname, vps_status, services_name, vps_comment from vps left join vps_masters on vps_server=vps_masters.vps_id left join services on services_id=vps.vps_type")
		->set_title("Virtual Private Servers (VPS)")
		->go();
}
