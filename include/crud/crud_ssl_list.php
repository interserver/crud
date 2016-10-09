<?php
function crud_ssl_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select ssl_id, ssl_hostname, services_name, ssl_status, ssl_company from orders left join services on ssl_type=services_id")
		->set_title("SSL Certificates")
		->go();
}
