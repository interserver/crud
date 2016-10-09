<?php
function crud_abuse() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from abuse")
		->set_title("Abuse")
		->go();
}
