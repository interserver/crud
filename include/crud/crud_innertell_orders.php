<?php
function crud_innertell_orders() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from orders")
		->set_title("Dedicated Server Orders")
		->go();
}
