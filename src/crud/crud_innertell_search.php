<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_innertell_search()
 * @return void
 */
function crud_innertell_search() {
	function_requirements('class.crud');
	crud::init("select 'Domain Names' as site, domain_id as id, account_lid as username, domain_hostname as product, domain_status as status  from domains left join accounts on domain_custid=account_id where  domain_id='__searchid__' or domain_hostname like '%__searchtxt__%'", 'domains')
		->set_title('Search Results')
		->go();
}
