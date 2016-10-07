<?php
function crud_history_log() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  from history_log")
		->set_title("History Log")
		->go();
}
