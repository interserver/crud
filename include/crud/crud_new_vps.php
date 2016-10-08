<?php
function crud_new_vps() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select vps_id, vps_hostname, vps_status, repeat_invoices_description as package, account_lid as owner, concat(extract(HOUR FROM timediff(now(), repeat_invoices_date)), ' hours') as age   from vps left join accounts on account_id=vps_custid left join repeat_invoices on repeat_invoices_id=vps_invoice ")
		->set_title("Newest VPS Signups")
		->go();
}