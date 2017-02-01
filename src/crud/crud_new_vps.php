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
 * crud_new_vps()
 * @return void
 */
function crud_new_vps() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select vps_id, vps_hostname, vps_status, repeat_invoices_description as package, account_lid as owner, concat(extract(HOUR FROM timediff(now(), repeat_invoices_date)), ' hours') as age   from vps left join accounts on account_id=vps_custid left join repeat_invoices on repeat_invoices_id=vps_invoice", 'vps')
		->set_title('Newest VPS Signups')
		->go();
}
