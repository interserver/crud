<?php
function crud_paypal_history() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from Get_PayPal_History")
		->set_title("Paypal History")
		->go();
}
