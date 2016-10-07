<?php
function crud_form_manager() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select * from forms")
		->set_title("Form Manager")
		->go();
}
