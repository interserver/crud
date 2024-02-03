<?php
/**
 * floating ips List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2023
 * @package MyAdmin
 * @category floating_ips
 */

use \MyCrud\Crud;

/**
 * Displays a list of all the mail's available to your current session
 *
 * @return void
 */
function crud_floating_ips_list()
{
    $module = 'floating_ips';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, repeat_invoices_cost, {$settings['PREFIX']}_ip, {$settings['PREFIX']}_status, services_name from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}' left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
        ->set_limit_custid_role('list_all')
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->enable_labels()
        ->set_labels([$settings['PREFIX'].'_id' => _('ID'),$settings['PREFIX'].'_ip' => _('IP'), 'repeat_invoices_cost' => _('Cost'), $settings['PREFIX'].'_status' => _('Status'), 'services_name' => _('Package')])
        ->add_header_button($GLOBALS['tf']->default_theme == 'adminlte' ? $GLOBALS['tf']->link('index.php', 'choice=none.order_floating_ip') : $GLOBALS['tf']->link('index.php', 'choice=none.buy_floating_ip'), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
        ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        //->enable_fluid_container()
        ->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? ($GLOBALS['tf']->ima == 'admin' ? '' : '4') : '').'&id=%id%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
        ->go();
}
