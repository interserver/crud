<?php
/**
 * Backups List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Backups
 */

use MyCrud\Crud;

/**
 * Displays a list of all the Backup's available to your current session
 *
 * @return void
 */
function crud_backups_list()
{
    $module = 'backups';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, {$settings['PREFIX']}_name, ifnull(repeat_invoices_cost, 0.00) as {$settings['PREFIX']}_cost, {$settings['PREFIX']}_username, {$settings['PREFIX']}_status, services_name from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}' left join {$settings['PREFIX']}_masters on {$settings['TABLE']}.{$settings['PREFIX']}_server={$settings['PREFIX']}_masters.{$settings['PREFIX']}_id left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->enable_labels()
        ->set_labels([$settings['PREFIX'].'_id' => _('ID'),$settings['PREFIX'].'_username' => _('Username'), $settings['PREFIX'].'_cost' => _('Cost'), $settings['PREFIX'].'_status' => _('Status'), $settings['PREFIX'].'_name' => _('Server'), 'services_name' => _('Package')])
        //->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.buy_'.$settings['PREFIX']), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
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
