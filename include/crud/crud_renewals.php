<?php
function crud_renewals() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_renewals")
		->set_title("Renewals")
		->go();
}
