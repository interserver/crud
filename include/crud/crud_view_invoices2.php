<?php
function crud_view_invoices2() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from invoices")
		->set_title("View Invoices List")
		->go();
}
