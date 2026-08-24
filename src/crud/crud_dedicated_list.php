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
 * crud_dedicated_list()
 * @return void
 */
function crud_dedicated_list()
{
    $query = "SELECT server_id, primary_ipv4, server_hostname, server_status, bin_to_uuid(servers.server_uuid) as service_uuid FROM servers LEFT JOIN assets ON order_id=server_id";
    $labels = ['server_id' => _('ID'), 'primary_ipv4' => 'IP', 'server_hostname' =>  _('Server Name'), 'server_status' => _('Status')];
    if (\MyAdmin\App::ima() == 'admin') {
        $query = "SELECT server_id, account_lid, primary_ipv4, server_hostname, server_status, bin_to_uuid(servers.server_uuid) as service_uuid FROM servers LEFT JOIN accounts on account_id=server_custid LEFT JOIN assets ON order_id=server_id";
        $labels = ['server_id' => _('ID'),'account_lid' => _('Client'), 'primary_ipv4' => 'IP', 'server_hostname' =>  _('Server Name'), 'server_status' => _('Status')];
    }
    Crud::init($query, 'servers')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Dedicated List'))
        ->set_order('server_id', 'desc')
        ->enable_labels()
        ->set_labels($labels)
        ->add_header_button(\MyAdmin\App::link('index.php', 'choice=none.order_server'), _('Deploy'), 'primary', 'cloud-upload-alt', _('Deploy Server'), 'client')
        ->add_title_search_button([['server_status','in',['active','active-billing']]], _('Active'), 'info active')
        ->add_title_search_button([['server_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([['server_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        // link this list's rows by their service_uuid instead of their sequential id.
        // both forms load the same page, and a row with no usable uuid keeps linking by id.
        ->use_uuid_links()
        ->add_row_button((\MyAdmin\App::ima() == 'admin' ? 'none.view_server_order&%uuid%' : 'none.view_server&%uuid%'), _('View Server'), 'primary', 'cog')
        ->go();
}
