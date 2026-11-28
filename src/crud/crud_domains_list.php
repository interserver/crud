<?php
/**
 * Domains List.
 *
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 *
 * @category Domains
 */
use MyCrud\Crud;

/**
 * the domains list's screenshot column: a shot.sh thumbnail of the hostname linked to the
 * domain's page (view_domain by id, as the sql built link was). the sql used to concat() this
 * html from the id and the hostname, unescaped; the hostname is a customer's, so here it is
 * url encoded into the image url and everything put into the html is escaped. a 'function'
 * filter, so the column shows the same on the rendered page and after an ajax refill (see
 * Crud::has_server_decoration()).
 *
 * @param string $field the column ('screenshot', which holds the domain id)
 * @param string $value the escaped column value (not used, the row has the plain values)
 * @param array $row the record as fetched
 * @return string html
 */
function crud_domains_list_screenshot($field, $value, $row = [])
{
    $settings = \get_module_settings('domains');
    $id = (string) ($row[$settings['PREFIX'].'_id'] ?? ($row[$field] ?? ''));
    $hostname = (string) ($row[$settings['PREFIX'].'_hostname'] ?? '');
    $link = Crud::link_url('index.php?choice=none.view_domain&id='.rawurlencode($id));
    $img = 'https://shot.sh?w=300&h=100&img='.rawurlencode($hostname);
    return '<a href="'.Crud::escape_cell($link).'"><img src="'.Crud::escape_cell($img).'"></a>';
}

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
    $defaultTimezone = getDefaultTimezone();
    $timezone = getTimezone();
    // the screenshot column is the domain id; crud_domains_list_screenshot() builds its linked
    // thumbnail from the row's id and hostname (it used to be concat()ed in the sql, which put
    // the hostname into the html unescaped)
    Crud::init("select {$settings['PREFIX']}_id as screenshot, {$settings['PREFIX']}_id,{$settings['PREFIX']}_hostname, if({$settings['PREFIX']}_expire_date is null, '', if({$settings['PREFIX']}_expire_date = '0000-00-00 00:00:00','', convert_tz({$settings['PREFIX']}_expire_date, '{$defaultTimezone}', '{$timezone}'))) as {$settings['PREFIX']}_expire_date, CONCAT(repeat_invoices_currency, ' ', repeat_invoices_cost) as cost, {$settings['PREFIX']}_status, bin_to_uuid({$settings['TABLE']}.{$settings['PREFIX']}_uuid) as service_uuid from {$settings['TABLE']} left join repeat_invoices on repeat_invoices_id={$settings['PREFIX']}_invoice and repeat_invoices_module='{$module}'", $module)
        ->set_limit_custid_role('list_all')
        ->add_filter('screenshot', 'crud_domains_list_screenshot', 'function')
    ->set_order($settings['PREFIX'].'_status', 'asc')
    ->set_title(_($settings['TITLE']).' '._('List'))
    ->add_header_button(\MyAdmin\App::defaultTheme() == 'adminlte' ? \MyAdmin\App::link('index.php', 'choice=none.'.$settings['PREFIX'].'_order') : \MyAdmin\App::link('index.php', 'choice=none.buy_new_'.$settings['PREFIX']), _('Deploy'), 'primary', 'cloud-upload-alt', _('Deploy').' '._($settings['TITLE']), 'client')
    ->add_title_search_button([[$settings['PREFIX'].'_status', '=', 'active']], _('Active'), 'info active')
    ->add_title_search_button([[$settings['PREFIX'].'_status', 'in', ['pending', 'pending-setup', 'pend-approval']]], _('Pending'), 'info')
    ->add_title_search_button([[$settings['PREFIX'].'_status', 'in', ['canceled', 'expired']]], _('Expired'), 'info')
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
