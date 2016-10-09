<?php
function crud_domains_list() {
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('admins_control')) {
		dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
		return false;
	}
	$module = 'domains';
	$settings = get_module_settings($module);
	page_title($settings['TITLE'] . ' List');
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select {$settings['PREFIX']}_id,{$settings['PREFIX']}_hostname,{$settings['PREFIX']}_cost,{$settings['PREFIX']}_status,{$settings['PREFIX']}_company from {$settings['TABLE']}", $module)
		->set_title($settings['TITLE'] . ' List')
		->add_header_button(array(array($settings['PREFIX'].'_status','=','active')),'Active','info')
		->add_header_button(array(array($settings['PREFIX'].'_status','in',array('pending','pending-setup','pend-approval'))),'Pending','info')
		->add_header_button(array(array($settings['PREFIX'].'_status','in',array('canceled','expired'))),'Expired','info')
		->add_header_button(array(),'All','info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '2' : '4') : '').'&id=\'+get_crud_row_id(this);" title="View '.$settings['TITLE'].'" data-title="View '.$settings['TITLE'].'"><i class="fa fa-fw fa-cog"></i></button>')
		->go();
}
