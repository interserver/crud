<?php
function crud_fantastico_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  get_fantastico_list")
		->set_title("Fantastico License List")
		->go();
}
