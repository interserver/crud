<?php
function crud_webhosting_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select websites.website_id as service_id, website_masters.website_name, website_cost, website_hostname, website_status, services_name, website_comment, '' as website_extra
 from websites left join website_masters on website_server=website_masters.website_id left join services on services_id=websites.website_type")
		->set_title("Website List")
		->go();
}
