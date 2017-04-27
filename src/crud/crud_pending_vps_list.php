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
use \MyCrud\Crud;

/**
 * crud_pending_vps_list()
 * @return void
 */
function crud_pending_vps_list() {
		Crud::init("select vps.vps_id, vps_masters.vps_name, vps_cost, vps_hostname, vps_status, services_name, vps_comment, account_value as maxmind_score, repeat_invoices_date from vps left join vps_masters on vps_server=vps_masters.vps_id left join services on services_id=vps.vps_type left join accounts on accounts.account_id=vps_custid left join accounts_ext on accounts_ext.account_id=vps_custid left join repeat_invoices on repeat_invoices_id=vps_invoice where account_key='maxmind_score' and account_status='active' and  cast(account_value as decimal) >= 2.5", 'vps')
		->set_title('Pending Virtual Private Servers (VPS)')
		->go();
}
