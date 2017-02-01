<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_whm_listaccts()
 * @return void
 */
function crud_whm_listaccts() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init('whm_get_accounts', 'default', 'function')
		->set_title('Accounts List')
		->go();
}