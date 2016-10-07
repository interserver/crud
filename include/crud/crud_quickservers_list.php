<?php
function crud_quickservers_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select quickservers.qs_id as service_id, qs_masters.qs_name, quickservers.qs_cost, qs_hostname, qs_status, services_name, qs_comment from quickservers left join qs_masters on qs_server=qs_masters.qs_id left join services on services_id=quickservers.qs_type
")
		->set_title("QuickServers")
		->go();
}
