<?php
function crud_server_billing_stats() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  get_server_billing_stats")
		->set_title("Server Billing Stats")
		->go();
}
