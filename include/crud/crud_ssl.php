<?php
function crud_ssl() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from orders", 'ssl')
		->set_title("SSL Certificates")
		->go();
}
