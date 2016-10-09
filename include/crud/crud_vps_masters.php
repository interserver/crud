<?php
function crud_vps_masters() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from vps_masters")
		->set_title("VPS Host Servers")
		->go();
}
