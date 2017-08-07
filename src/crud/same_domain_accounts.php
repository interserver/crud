<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * same_domain_accounts()
 * @return void
 */
function same_domain_accounts() {
	function_requirements('class.Crud');
	$id = (int)$GLOBALS['tf']->variables->request['id'];
	Crud::init("select account_id,account_lid,account_status from accounts where account_lid like (select concat('%',substring(account_lid, locate('@', account_lid))) from accounts where account_id={$id})")
		->set_title("Accounts matching the @domain.com of client {$id}")
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('none.edit_customer3&customer=%id%', 'Edit Customer', 'primary', 'user')
		->set_extra_url_args('&id='.$id)
		->go();
}
