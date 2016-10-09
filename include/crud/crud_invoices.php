<?php
function crud_invoices() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from invoices")
		->set_title("Invoices")
		->go();
}
