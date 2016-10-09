<?php
function crud_repeat_invoices() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from repeat_invoices")
		->set_title("Repeat Invoices")
		->go();
}
