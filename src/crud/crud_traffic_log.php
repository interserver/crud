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
 * crud_coupons()
 * @return void
 */
function crud_traffic_log()
{
    page_title(_('Traffic Log'));
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    // IF(accounts_ext.account_id is null,'<i class=\"fa fa-remove\">',concat('<a href=\"index.php?choice=none.edit_customer&customer=',accounts_ext.account_id,'\"><i class=\"fa fa-search\"></i></a>')) AS affiliate
    // LEFT JOIN accounts_ext ON account_key = 'referrer_coupon' AND account_value = name
    Crud::init("select id, timestamp, duration, method, status, custid, sessionid, client_ip, substring(uri, 1, 75) as uri from traffic_log")
        ->set_title(_('Traffic Log'))
        ->enable_labels()
        ->set_order('id', 'desc')
        ->disable_delete()
        ->disable_edit()
        //->add_search_param('client_ip', 'ip')
        ->add_row_button('none.traffic_log&id=%id%', _('View'), 'primary', 'cog')
        ->enable_fluid_container()
        ->set_page_limit(25)
        ->go();
}
