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
 * crud_quickservers_list()
 * @return void
 */
function crud_quickservers_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select quickservers.qs_id as service_id, qs_masters.qs_name, quickservers.qs_cost, qs_hostname, qs_status, services_name, qs_comment from quickservers left join qs_masters on qs_server=qs_masters.qs_id left join services on services_id=quickservers.qs_type", 'quickservers')
		->set_title("QuickServers")
		->go();
}
