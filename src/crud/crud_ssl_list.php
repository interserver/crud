<?php
/**
 * SSL Certificates List
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
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
    Crud::init("select {$settings['PREFIX']}_id, {$settings['PREFIX']}_hostname, services_name, {$settings['PREFIX']}_status, {$settings['PREFIX']}_company, bin_to_uuid(ssl_certs.{$settings['PREFIX']}_uuid) as service_uuid from ssl_certs left join services on {$settings['PREFIX']}_type=services_id", $module)
        ->set_limit_custid_role('list_all')
        ->set_order($settings['PREFIX'].'_status', 'asc')
        ->set_title(_($settings['TITLE']).' '._('List'))
        ->add_header_button(\MyAdmin\App::link('index.php', 'choice=none.buy_'.$settings['PREFIX']), _('Deploy'), 'primary', 'cloud-upload-alt', _('Deploy').' '._($settings['TITLE']), 'client')
        ->add_title_search_button([[$settings['PREFIX'].'_status','=','active']], _('Active'), 'info active')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['pending','pending-setup','pend-approval']]], _('Pending'), 'info')
        ->add_title_search_button([[$settings['PREFIX'].'_status','in',['canceled','expired']]], _('Expired'), 'info')
        ->add_title_search_button([], _('All'), 'info')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        // link this list's rows by their service_uuid instead of their sequential id.
        // both forms load the same page, and a row with no usable uuid keeps linking by id.
        ->use_uuid_links()
        ->add_row_button('none.view_'.$settings['PREFIX'].($module == 'webhosting' ? (\MyAdmin\App::ima() == 'admin' ? '' : '4') : '').'&%uuid%', _('View').' '._($settings['TITLE']), 'primary', 'cog')
        ->go();
}
