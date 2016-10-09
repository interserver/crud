<?php
function crud_domains() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from domains")
		->set_title("Domains")
		->go();
}
