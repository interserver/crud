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
 * same_domain_accounts()
 * @return false|void
 */
function same_domain_accounts()
{
    // admin only: the query is over accounts, which has no *_custid column for
    // limit_custid to filter on, so it lists every account on the domain
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('view_customer')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    $id = (int)\MyAdmin\App::variables()->request['id'];
    Crud::init("select account_id,account_lid,account_status from accounts where account_lid like (select concat('%',substring(account_lid, locate('@', account_lid))) from accounts where account_id={$id})")
        ->set_title(_('Accounts matching the').' @domain.com '._('of client').' '.$id)
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->add_row_button('none.edit_customer&customer=%id%', _('Edit Customer'), 'primary', 'user')
        ->set_extra_url_args('&id='.$id)
        ->go();
}
