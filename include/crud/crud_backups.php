<?php
function crud_backups() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select * from backups", 'backups')
		->set_title("Backups")
		->go();
}
