<?php
function crud_session_log() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from session_log")
		->set_title("session log")
		->go();
}
