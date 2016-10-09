<?php
function crud_backups_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select backups.backup_id, backup_name, backup_username, services_name, backup_cost, backup_status from backups left join backup_masters on backups.backup_server=backup_masters.backup_id left join services on services_id=backups.backup_type", 'backups')
		->set_title("Backup List")
		->add_header_button(array(array('backup_status','=','active')),'Active','info')
		->add_header_button(array(array('backup_status','in',array('pending','pending-setup','pend-approval'))),'Pending','info')
		->add_header_button(array(array('backup_status','in',array('canceled','expired'))),'Expired','info')
		->add_header_button(array(),'All','info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.view_backup&id=\'+get_crud_row_id(this);" title="View Backup" data-title="View Backup"><i class="fa fa-fw fa-cog"></i></button>')
		->go();
}
