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
 * crud_licenses_list()
 * @return void
 */
function crud_licenses_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select license_id, license_hostname, license_ip, services_name, license_cost, license_status, invoices_paid, invoices_date from licenses left join invoices on invoices_extra=license_invoice and invoices_id=(select max(invoices_id) from invoices where invoices_type=1 and  invoices_extra=license_invoice) left join services on services_id=license_type where 1=1", 'licenses')
		->set_title("License List")
		->go();
}
