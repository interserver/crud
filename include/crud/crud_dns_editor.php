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
 * crud_dns_editor()
 * @return void
 */
function crud_dns_editor() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$domain_id = (int)$GLOBALS['tf']->variables->request['id'];
	crud::init("select * from records where domain_id='{$domain_id}'", 'pdns')
		->set_title("DNS Editor")
		->enable_fluid_container()
		->set_extra_url_args('&id='.$domain_id)
		->go();
}
