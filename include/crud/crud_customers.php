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
 * crud_customers()
 * @return void
 */
function crud_customers() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select account_id,account_lid,account_status, count(__PREFIX___custid) as order_count from accounts left join __TABLE__ on __PREFIX___custid=account_id where account_ima != 'admin' group by account_id")
		->set_title("Customers")
		->go();
}
