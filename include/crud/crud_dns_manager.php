<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2016
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_dns_manager()
 * @return void
 */
function crud_dns_manager() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select domains.id, domains.account, domains.name, records.content from domains left join records on domains.id=records.domain_id where ((records.type='A' and (records.name=domains.name or records.name='') ) or records.type is null)", 'pdns')
		->set_title("DNS Manager")
		->go();
}
