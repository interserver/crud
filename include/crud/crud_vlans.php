<?php
function crud_vlans() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from vlans")
		->set_title("IP VLAN Manager")
		->go();
}
