<?php
function crud_innertell_search() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select 'Domain Names' as site, domain_id as id, account_lid as username, domain_hostname as product, domain_status as status  from domains left join accounts on domain_custid=account_id where  domain_id='__searchid__' or domain_hostname like '%__searchtxt__%'")
		->set_title("Search Results")
		->go();
}
