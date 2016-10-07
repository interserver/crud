<?php
function crud_vps() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  from vps")
		->set_title("Virtual Private Servers")
		->go();
}
