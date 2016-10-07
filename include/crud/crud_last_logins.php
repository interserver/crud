<?php
function crud_last_logins() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select access_ip, access_login from access_log where access_ip != '' group by access_ip")
		->set_title("Last Logn Locations")
		->go();
}
