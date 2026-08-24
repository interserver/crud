<?php
/**
 * Webhosting List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Webhosting
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the Websites's available to your current session
 *
 * @return void
 */
function crud_webhosting_list()
{
    $module = 'webhosting';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['TABLE']}.{$settings['PREFIX']}_id, {$settings['PREFIX']}_hostname, CONCAT(repeat_invoices_currency, ' ', repeat_invoices_cost) AS cost, {$settings['PREFIX']}_status, services_name, {$settings['PREFIX']}_comment, bin_to_uuid({$settings['TABLE']}.{$settings['PREFIX']}_uuid) as service_uuid from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}' left join {$settings['PREFIX']}_masters on {$settings['PREFIX']}_server={$settings['PREFIX']}_masters.{$settings['PREFIX']}_id left join services on services_id={$settings['TABLE']}.{$settings['PREFIX']}_type", $module)
        ->set_limit_custid_role('list_all')
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->enable_labels()
        ->set_labels([$settings['PREFIX'].'_id' => _('ID'),$settings['PREFIX'].'_hostname' => _('Hostname'), 'repeat_invoices_cost' => _('Cost'), $settings['PREFIX'].'_status' => _('Status'), $settings['PREFIX'].'_comment' => _('Comments'), 'services_name' => _('Package')])
        ->add_header_button(\MyAdmin\App::defaultTheme() == 'adminlte' ? \MyAdmin\App::link('index.php', 'choice=none.order_'.$settings['PREFIX']) : \MyAdmin\App::link('index.php', 'choice=none.buy_'.$settings['PREFIX']), _('Deploy'), 'primary', 'cloud-upload-alt', _('Deploy').' '._($settings['TITLE']), 'client')
//		->set_default_search([[$settings['PREFIX'].'_status','=','active']])
        ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval','active','canceled','expired']]], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        // link this list's rows by their service_uuid instead of their sequential id.
        // both forms load the same page, and a row with no usable uuid keeps linking by id.
        ->use_uuid_links()
        ->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? (\MyAdmin\App::ima() == 'admin' || \MyAdmin\App::defaultTheme() == 'adminlte' ? '' : '4') : '').'&%uuid%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
        ->go();
}
