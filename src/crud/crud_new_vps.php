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
 * crud_new_vps()
 * @return void
 */
function crud_new_vps() {
		Crud::init("select vps_id, vps_hostname, vps_status, repeat_invoices_description as package, account_lid as owner, concat(extract(HOUR FROM timediff(now(), repeat_invoices_date)), ' hours') as age   from vps left join accounts on account_id=vps_custid left join repeat_invoices on repeat_invoices_id=vps_invoice", 'vps')
		->set_title('Newest VPS Signups')
		->go();
}
