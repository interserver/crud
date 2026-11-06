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
 * crud_vps_list_free_ips2()
 * @return false|void
 */
function crud_vps_list_free_ips2()
{
    // admin only, like the View Available IPs page (vps_list_free_ips, module_config):
    // every host's free ips with host name and location
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('module_config')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    Crud::init('get_vps_free_ips', 'vps', 'function')
        ->set_title(_('Available IPs For VPS Servers'))
        ->disable_delete()
        ->disable_edit()
        ->go();
}
