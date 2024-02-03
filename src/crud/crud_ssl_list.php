<?php
/**
 * SSL Certificates List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category SSL
 */
use \MyCrud\Crud;

/**
 * Displays a list of all the SSL Certificates's available to your current session
 *
 * @return void
 */
function crud_ssl_list()
{
    $module = 'ssl';
    $settings = \get_module_settings($module);
    page_title(_($settings['TITLE']).' '._('List'));
    Crud::init("select {$settings['PREFIX']}_id, {$settings['PREFIX']}_hostname, services_name, {$settings['PREFIX']}_status, {$settings['PREFIX']}_company from ssl_certs left join services on {$settings['PREFIX']}_type=services_id", $module)
        ->set_limit_custid_role('list_all')
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.buy_'.$settings['PREFIX']), _('Order'), 'primary', 'shopping-cart', _('Order').' '._($settings['TITLE']), 'client')
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
