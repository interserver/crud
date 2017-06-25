<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_table()
 * @return void
 */
function crud_table() {
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('admins_control')) {
		dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
		return FALSE;
	}
	$module = $GLOBALS['tf']->variables->request['db'];
	$table = $GLOBALS['tf']->variables->request['table'];
	$db = get_module_db($module);
	page_title("{$db->Database} {$table} Table Data Browser");
		Crud::init($table, $module)
		->set_extra_url_args("&db={$module}&table={$table}")
		->set_title("{$db->Database}.{$table} table data browser")
		->enable_fluid_container()
		->go();
}
