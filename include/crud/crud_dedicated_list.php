<?php
function crud_dedicated_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from orders")
		->set_title("Dedicated List")
		->go();
}
