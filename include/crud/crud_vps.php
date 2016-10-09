<?php
function crud_vps() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from vps", 'vps')
		->set_title("Virtual Private Servers")
		->go();
}
