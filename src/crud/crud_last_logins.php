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
 * crud_last_logins()
 * @return false|void
 */
function crud_last_logins()
{
    // admin only: this lists every customer's rows (the table has no *_custid column for
    // limit_custid to filter on) and index.php/ajax.php reach any registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('list_all')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    add_output(alert('TODO', 'Get Client Side Working Right'));
    Crud::init("select access_ip, access_login from access_log where access_ip != '' group by access_ip")
        ->set_title(_('Last Logn Locations'))
        ->disable_delete()
        ->disable_edit()
        ->go();
}
