<?php
function crud_backups() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from backups")
		->set_title("Backups")
		->go();
}
