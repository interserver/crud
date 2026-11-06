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
 * crud_vlans()
 * @return false|void
 */
function crud_vlans()
{
    // admin only: this lists every customer's rows (the table has no *_custid column for
    // limit_custid to filter on) and index.php/ajax.php reach any registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('system_config')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    add_output(alert('TODO', 'Get Adding a VLAN working well, maybe some totals/stats type bottom row'));
    Crud::init('select * from vlans', 'domains')
        ->set_title(_('IP VLAN Manager'))
        ->go();
}
