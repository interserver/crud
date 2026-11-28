<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_table()
 *
 * browses one table of a module database. the request db and table went straight into
 * Crud::init(), which runs any value holding a space as a whole query, so
 * table=select account_passwd as p from accounts listed the hashes unmasked (the alias is not
 * a secret column) and the edit handler then wrote 'update accounts set account_passwd=...'.
 * now db has to be one of the module databases the browser is linked with
 * (Crud::$table_browser_modules), table a plain name of a table that exists in that database,
 * and init() is told it is a table, never a query.
 *
 * @return bool
 */
function crud_table()
{
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('admins_control')) {
        dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
        return false;
    }
    $module = \MyAdmin\App::variables()->request['db'] ?? null;
    $table = \MyAdmin\App::variables()->request['table'] ?? null;
    if (!is_string($module) || !in_array($module, Crud::$table_browser_modules, true)) {
        dialog(_('Invalid Database'), _('Unknown database, open the table from the CRUD table list.'));
        return false;
    }
    if (!is_string($table) || !preg_match('/^[A-Za-z0-9_]+$/', $table)) {
        dialog(_('Invalid Table'), _('Unknown table, open the table from the CRUD table list.'));
        return false;
    }
    $db = get_module_db($module);
    // the name only holds [A-Za-z0-9_] and is escaped anyway; it has to be a table (or view) of
    // this module's database, not just any identifier
    $db->query("select table_name from information_schema.tables where table_schema=database() and table_name='".$db->real_escape($table)."'", __LINE__, __FILE__);
    if ($db->num_rows() < 1) {
        dialog(_('Invalid Table'), _('Unknown table, open the table from the CRUD table list.'));
        return false;
    }
    page_title("{$db->database} {$table} Table Data Browser");
    Crud::init($table, $module, 'table')
        ->set_extra_url_args("&db={$module}&table={$table}")
        ->set_title("{$db->database}.{$table} table data browser")
        ->enable_fluid_container()
        ->go();
}
