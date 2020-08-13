<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_table()
 *
 * @return bool
 */
function crud_table()
{
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('admins_control')) {
		dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
		return false;
	}
	$module = $GLOBALS['tf']->variables->request['db'];
	$table = $GLOBALS['tf']->variables->request['table'];
	$db = get_module_db($module);
	page_title("{$db->database} {$table} Table Data Browser");
	Crud::init($table, $module)
		->set_extra_url_args("&db={$module}&table={$table}")
		->set_title("{$db->database}.{$table} table data browser")
		->enable_fluid_container()
		->go();
}
