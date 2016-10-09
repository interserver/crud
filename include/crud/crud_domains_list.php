<?php
function crud_domains_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select domain_id,domain_hostname,domain_cost,domain_status,domain_company from domains", 'domains')
		->set_title("Domains Registrations")
		->add_header_button(array(array('domain_status','=','active')),'Active','info')
		->add_header_button(array(array('domain_status','in',array('pending','pending-setup','pend-approval'))),'Pending','info')
		->add_header_button(array(array('domain_status','in',array('canceled','expired'))),'Expired','info')
		->add_header_button(array(),'All','info active')
		->disable_delete()
		->disable_edit()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.view_domain&id=\'+get_crud_row_id(this);" title="View Domain" data-title="View Domain"><i class="fa fa-fw fa-cog"></i></button>')
		->go();
}
