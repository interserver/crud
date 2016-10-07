<?php
function crud_paypal_transactions() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  from paypal")
		->set_title("Paypal Transactions")
		->go();
}
