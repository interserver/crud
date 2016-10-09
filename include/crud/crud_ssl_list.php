<?php
function crud_ssl_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select ssl_id, ssl_hostname, services_name, ssl_status, ssl_company from orders left join services on ssl_type=services_id", 'ssl')
		->set_title("SSL Certificates")
		->add_header_button(array(array('ssl_status','=','active')),'Active','info')
		->add_header_button(array(array('ssl_status','in',array('pending','pending-setup','pend-approval'))),'Pending','info')
		->add_header_button(array(array('ssl_status','in',array('canceled','expired'))),'Expired','info')
		->add_header_button(array(),'All','info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.view_ssl&id=\'+get_crud_row_id(this);" title="View SSL Certificate" data-title="View SSL Certificate"><i class="fa fa-fw fa-cog"></i></button>')
		->go();
}
