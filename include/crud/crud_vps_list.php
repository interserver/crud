<?php
function crud_vps_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select vps.vps_id, vps_masters.vps_name, vps_cost, vps_hostname, vps_status, services_name, vps_comment from vps left join vps_masters on vps_server=vps_masters.vps_id left join services on services_id=vps.vps_type", 'vps')
		->set_title("Virtual Private Servers (VPS)")
		->add_header_button(array(array('vps_status','=','active')),'Active','info')
		->add_header_button(array(array('vps_status','in',array('pending','pending-setup','pend-approval'))),'Pending','info')
		->add_header_button(array(array('vps_status','in',array('canceled','expired'))),'Expired','info')
		->add_header_button(array(),'All','info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.view_vps3&id=\'+get_crud_row_id(this);" title="View VPS" data-title="View VPS"><i class="fa fa-fw fa-cog"></i></button>')
		->go();
}
