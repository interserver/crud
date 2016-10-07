<?php
function crud_whm_listaccts() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  whm_get_accounts")
		->set_title("Accounts List")
		->go();
}
