<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2016
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_same_domain_accounts()
 * @return void
 */
function crud_same_domain_accounts() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$id = intval($GLOBALS['tf']->variables->request['id']);
	crud::init("select account_id,account_lid,account_status from accounts where account_lid like (select concat('%',substring(account_lid, locate('@', account_lid))) from accounts where account_id={$id})")
		->set_title("Accounts matching the @domain.com of client {$id}")
		->disable_delete()
		->disable_edit()
		->enable_fluid_container()
		->add_row_button('<button type="button" class="btn btn-primary btn-xs" onclick="window.location=\'index.php?choice=none.edit_customer3&id=\'+get_crud_row_id(this);" title="Goto Edit Customer" data-title="Goto Edit Customer"><i class="fa fa-fw fa-cog"></i></button>')
		->set_extra_url_args('&id='.$id)
		->go();
}
