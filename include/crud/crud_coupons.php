<?php
function crud_coupons() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select id,customer,usable,applies,type,amount,name,onetime,account_lid from coupons left join accounts on account_id=customer")
		->set_title("Coupons")
		->go();
}
