<?php
/**
 * QuickServers List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category QuickServers
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the QuickServer's available to your current session
 *
 * @return void
 */
function crud_quickservers_list()
{
    $module = 'quickservers';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, {$settings['PREFIX']}_name, repeat_invoices_cost as cost, {$settings['PREFIX']}_hostname, {$settings['PREFIX']}_status, {$settings['PREFIX']}_comment from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}' left join {$settings['PREFIX']}_masters on {$settings['PREFIX']}_server={$settings['PREFIX']}_masters.{$settings['PREFIX']}_id", $module)
        ->set_limit_custid_role('list_all')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->add_header_button($GLOBALS['tf']->default_theme == 'adminlte' ? $GLOBALS['tf']->link('index.php', 'choice=none.order_quickserver') : $GLOBALS['tf']->link('index.php', 'choice=none.buy_qs'), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
        ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '' : '4') : '').'&id=%id%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
        ->go();
}
