<?php
function crud_dns_manager() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select domains.id, domains.account, domains.name, records.content from domains left join records on domains.id=records.domain_id where ((records.type='A' and (records.name=domains.name or records.name='') ) or records.type is null)", 'powerdns')
		->set_title("DNS Manager")
		->go();
}
