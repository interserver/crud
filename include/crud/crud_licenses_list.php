<?php
function crud_licenses_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select license_id, license_hostname, license_ip, services_name, license_cost, license_status, invoices_paid, invoices_date from licenses left join invoices on invoices_extra=license_invoice and invoices_id=(select max(invoices_id) from invoices where invoices_type=1 and  invoices_extra=license_invoice) left join services on services_id=license_type where 1=1")
		->set_title("License List")
		->go();
}
