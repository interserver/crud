<?php
function crud_reusable_fantastico() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_reusable_fantastico")
		->set_title("Reusable Fantastico")
		->go();
}
