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
 * crud_dns_editor()
 * @return void
 */
function crud_dns_editor()
{
    $domain_id = (int)\MyAdmin\App::variables()->request['id'];
    // records has no account column so the crud cannot limit it by customer itself; the domain
    // must belong to the caller (admins with view_service see any) before any of its records are shown
    function_requirements('get_dns_domain');
    if (get_dns_domain($domain_id, false, 'view_service') === false) {
        $error = _("There was an error with the query, or you dont have access to that domain or it doesn't exist");
        if (isset(\MyAdmin\App::variables()->request['action'])) {
            echo $error;
        } else {
            add_output($error);
        }
        return;
    }
    $crud = Crud::init("select * from records where domain_id='{$domain_id}'", 'pdns', 'sql', '/^account$/m')
        ->set_title(_('DNS Editor'))
        ->enable_fluid_container()
        ->set_extra_url_args('&id='.$domain_id);
    // an edit is keyed by the record id alone, not the domain, so it stays admin-only
    if (\MyAdmin\App::ima() != 'admin') {
        $crud->disable_edit()
            ->disable_add()
            ->disable_delete();
    }
    $crud->go();
}
