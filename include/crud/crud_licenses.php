<?php
function crud_licenses() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from licenses")
		->set_title("Licenses")
		->go();
}
