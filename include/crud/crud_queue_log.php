<?php
function crud_queue_log() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  ")
		->set_title("Queue Log")
		->go();
}
