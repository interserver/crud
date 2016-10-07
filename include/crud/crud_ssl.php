<?php
function crud_ssl() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  from orders")
		->set_title("SSL Certificates")
		->go();
}
