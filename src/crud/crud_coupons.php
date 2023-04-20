<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_coupons()
 * @return void
 */
function crud_coupons()
{
	page_title(_('Coupons List'));
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
		dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
		return false;
	}
	// IF(accounts_ext.account_id is null,'<i class=\"fa fa-remove\">',concat('<a href=\"index.php?choice=none.edit_customer&customer=',accounts_ext.account_id,'\"><i class=\"fa fa-search\"></i></a>')) AS affiliate
	// LEFT JOIN accounts_ext ON account_key = 'referrer_coupon' AND account_value = name
	Crud::init("SELECT id,name,IF(type = 1,'"._('Percentage Off')."',IF(type = 2,'"._('Fixed Amount Off')."','"._('Specified Price')."')) as type,IF(type = 1,concat('-',amount,'%'),IF(type = 2,concat('-$',amount),concat('$',amount))) as amount,IF(customer = -1,'"._('All Customers')."',IF(customer = 0,'"._('None')."',IF(account_lid is not null,concat('<a href=\"index.php?choice=none.edit_customer&customer=',customer,'\">',account_lid,'</a>'),customer))) as customer,IF(onetime = 1,'"._('First Cycle Only')."','"._('Permanent Adjustment')."') as onetime,IF(usable = -1,'"._('Unlimited')."',IF(usable = 1,concat(usable,'"._('Time')."'),concat(usable, '"._('Times')."'))) as usable,IF(applies = -1,'"._('All Packages')."',IF(applies = 0,'"._('No Packages')."',applies)) as applies,module FROM coupons LEFT JOIN accounts ON accounts.account_id = customer where amount != 0.01")
		->set_title(_('Coupons'))
		->enable_labels()
		->set_labels(['id' => _('ID'),'name' => _('Coupon Name'), 'amount' => _('Amount'), 'customer' => _('Customer'), 'usable' => _('Usable'), 'applies' => _('Applies'), 'type' => 'Type', 'onetime' => 'One Time', 'module' => 'Module', 'affiliate' => 'Affiliate'])
		->add_header_button($GLOBALS['tf']->link('index.php', 'choice=none.coupons'), _('Manage Coupons'), 'primary', 'pencil', _('Manage Coupons'))
		->set_order('id', 'asc')
		->add_title_search_button([['onetime','=',1],['usable','=',1],['amount','=',0.01]], _('Affiliate Coupons'), 'info')
		->add_title_search_button([], _('All'), 'info active')
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
        ->add_row_button('none.edit_coupon&id=%id%', _('Edit Coupon'), 'primary', 'cog')
		->go();
}
