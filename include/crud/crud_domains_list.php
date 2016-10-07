<?php
function crud_domains_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select domain_id,domain_hostname,services_name,domain_cost,domain_status,domain_company from domains left join services on services_id=domain_type")
		->set_title("Domains")
		->go();
}
