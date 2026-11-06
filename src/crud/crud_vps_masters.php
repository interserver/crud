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
 * crud_vps_masters()
 * @return false|void
 */
function crud_vps_masters()
{
    // admin only, and only the admins who manage host server settings: every host's
    // row, ipmi and queue settings (vps_queue_key itself is a Crud secret column)
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('system_config')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('select * from vps_masters', 'vps')
        ->set_title(_('VPS Host Servers'))
        ->go();
}
