<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_innertell_search()
 * @return void
 */
function crud_innertell_search()
{
	Crud::init("select 'Domain Names' as site, domain_id as id, account_lid as username, domain_hostname as product, domain_status as status  from domains left join accounts on domain_custid=account_id where  domain_id='__searchid__' or domain_hostname like '%__searchtxt__%'", 'servers')
		->set_title('Search Results')
		->go();
}
