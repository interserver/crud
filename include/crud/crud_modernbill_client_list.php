<?php
function crud_modernbill_client_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from client_info", 'mb')
		->set_title("Modernbill Client List")
		->go();
}
