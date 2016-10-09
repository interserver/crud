<?php
function crud_backups_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select backups.backup_id, backup_name, backup_username, services_name, backup_cost, backup_status from backups left join backup_masters on backups.backup_server=backup_masters.backup_id left join services on services_id=backups.backup_type")
		->set_title("Backup List")
		->go();
}
