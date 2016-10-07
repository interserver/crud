<?php
function crud_customers() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select account_id,account_lid,account_status, count(__PREFIX___custid) as order_count from accounts left join __TABLE__ on __PREFIX___custid=account_id where account_ima != 'admin' group by account_id")
		->set_title("Customers")
		->go();
}
