<?php
function crud_user_log() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  from user_log")
		->set_title("User Log")
		->go();
}
