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
 * crud_dedicated_list()
 * @return void
 */
function crud_dedicated_list()
{
    $query = "SELECT server_id, primary_ipv4, server_hostname, server_status FROM servers LEFT JOIN assets ON order_id=server_id";
    $labels = ['server_id' => _('ID'), 'primary_ipv4' => 'IP', 'server_hostname' =>  _('Server Name'), 'server_status' => _('Status')];
    if ($GLOBALS['tf']->ima == 'admin') {
        $query = "SELECT server_id, account_lid, primary_ipv4, server_hostname, server_status FROM servers LEFT JOIN accounts on account_id=server_custid LEFT JOIN assets ON order_id=server_id";
        $labels = ['server_id' => _('ID'),'account_lid' => _('Client'), 'primary_ipv4' => 'IP', 'server_hostname' =>  _('Server Name'), 'server_status' => _('Status')];
    }
    Crud::init($query, 'servers')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Dedicated List'))
        ->set_order('server_id', 'desc')
        ->enable_labels()
        ->set_labels($labels)
        ->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.order_server'), _('Order'), 'primary', 'shopping-cart', _('Order Server'), 'client')
        ->add_title_search_button([['server_status','in',['active','active-billing']]], _('Active'), 'info active')
        ->add_title_search_button([['server_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([['server_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        ->add_row_button(($GLOBALS['tf']->ima == 'admin' ? 'none.view_server_order&id=%id%' : 'none.view_server&id=%id%'), _('View Server'), 'primary', 'cog')
        ->go();
}
