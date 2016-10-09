<?php
function crud_dedicated_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from orders", 'innertell')
		->set_title("Dedicated List")
		->go();
}
