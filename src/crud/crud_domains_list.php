<?php
/**
 * Domains List.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 *
 * @category Domains
 */
use MyCrud\Crud;

/**
 * Displays a list of all the Domains's available to your current session.
 *
 * @return void
 */
function crud_domains_list()
{
    $module = 'domains';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select concat('<a href=\"index.php?choice=none.view_domain&id=',{$settings['PREFIX']}_id,'\"><img src=\"https://shot.sh?w=300&h=100&img=',{$settings['PREFIX']}_hostname,'\"></a>') as screenshot, {$settings['PREFIX']}_id,{$settings['PREFIX']}_hostname, if({$settings['PREFIX']}_expire_date is null, '', if({$settings['PREFIX']}_expire_date = '0000-00-00 00:00:00','', {$settings['PREFIX']}_expire_date)) as {$settings['PREFIX']}_expire_date, repeat_invoices_cost as cost, {$settings['PREFIX']}_status from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}'", $module)
        ->set_limit_custid_role('list_all')
    ->set_order($settings['PREFIX'].'_status', 'asc')
    ->set_title(_($settings['TITLE']).' '._('List'))
    ->add_header_button($GLOBALS['tf']->default_theme == 'adminlte' ? $GLOBALS['tf']->link('index.php', 'choice=none.'.$settings['PREFIX'].'_order') : $GLOBALS['tf']->link('index.php', 'choice=none.buy_new_'.$settings['PREFIX']), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
    ->add_title_search_button([[$settings['PREFIX'].'_status', '=', 'active']], _('Active'), 'info active')
    ->add_title_search_button([[$settings['PREFIX'].'_status', 'in', ['pending', 'pending-setup', 'pend-approval']]], _('Pending'), 'info')
    ->add_title_search_button([[$settings['PREFIX'].'_status', 'in', ['canceled', 'expired']]], _('Expired'), 'info')
    ->add_title_search_button([], _('All'), 'info')
    ->disable_delete()
    ->disable_edit()
    ->enable_fluid_container()
    ->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '' : '4') : '').'&id=%id%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
    ->go();
}
