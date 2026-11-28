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
 * the coupons list's customer column: the account's login linked to edit_customer when the
 * coupon is for an account that exists (the row's customer_link_id), otherwise the label
 * ('All Customers', 'None') or the plain id, as the sql built it before. the sql used to concat()
 * the link with the login unescaped; here the text and the link are escaped. a 'function'
 * filter, so the column shows the same on the rendered page and after an ajax refill (see
 * Crud::has_server_decoration()).
 *
 * @param string $field the column ('customer')
 * @param string $value the escaped column value (not used, the row has the plain values)
 * @param array $row the record as fetched
 * @return string html
 */
function crud_coupons_customer($field, $value, $row = [])
{
    $text = Crud::escape_cell((string) ($row[$field] ?? ''));
    $id = (int) ($row['customer_link_id'] ?? 0);
    if ($id === 0) {
        return $text;
    }
    $link = Crud::link_url('index.php?choice=none.edit_customer&customer='.$id);
    return '<a href="'.Crud::escape_cell($link).'">'.$text.'</a>';
}

/**
 * crud_coupons()
 * @return void
 */
function crud_coupons()
{
    page_title(_('Coupons List'));
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    // IF(accounts_ext.account_id is null,'<i class=\"fa fa-remove\">',concat('<a href=\"index.php?choice=none.edit_customer&customer=',accounts_ext.account_id,'\"><i class=\"fa fa-search\"></i></a>')) AS affiliate
    // LEFT JOIN accounts_ext ON account_key = 'referrer_coupon' AND account_value = name
    // the customer column is plain text (the label, the account's login, or the id) and the
    // hidden customer_link_id the account to link to (0: no link); crud_coupons_customer()
    // builds the edit_customer link from them. the sql used to concat() that <a> with the
    // login in it unescaped
    Crud::init("SELECT id,name,IF(type = 1,'"._('Percentage Off')."',IF(type = 2,'"._('Fixed Amount Off')."','"._('Specified Price')."')) as type,IF(type = 1,concat('-',amount,'%'),IF(type = 2,concat('-$',amount),concat('$',amount))) as amount,IF(customer = -1,'"._('All Customers')."',IF(customer = 0,'"._('None')."',IF(account_lid is not null,account_lid,customer))) as customer,IF(onetime = 1,'"._('First Cycle Only')."','"._('Permanent Adjustment')."') as onetime,IF(usable = -1,'"._('Unlimited')."',IF(usable = 1,concat(usable,'"._('Time')."'),concat(usable, '"._('Times')."'))) as usable,IF(applies = -1,'"._('All Packages')."',IF(applies = 0,'"._('No Packages')."',applies)) as applies,module,IF(customer = -1,0,IF(customer = 0,0,IF(account_lid is not null,customer,0))) as customer_link_id FROM coupons LEFT JOIN accounts ON accounts.account_id = customer where amount != 0.01")
        ->set_title(_('Coupons'))
        ->hide_field('customer_link_id')
        ->add_filter('customer', 'crud_coupons_customer', 'function')
        ->enable_labels()
        ->set_labels(['id' => _('ID'),'name' => _('Coupon Name'), 'amount' => _('Amount'), 'customer' => _('Customer'), 'usable' => _('Usable'), 'applies' => _('Applies'), 'type' => 'Type', 'onetime' => 'One Time', 'module' => 'Module', 'affiliate' => 'Affiliate'])
        ->add_header_button(\MyAdmin\App::link('index.php', 'choice=none.coupons'), _('Manage Coupons'), 'primary', 'pencil', _('Manage Coupons'))
        ->set_order('id', 'asc')
        ->add_title_search_button([['onetime','=',1],['usable','=',1],['amount','=',0.01]], _('Affiliate Coupons'), 'info')
        ->add_title_search_button([], _('All'), 'info active')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->add_row_button('none.edit_coupon&id=%id%', _('Edit Coupon'), 'primary', 'cog')
        ->go();
}
