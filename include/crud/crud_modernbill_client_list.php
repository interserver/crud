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
 * crud_modernbill_client_list()
 * @return void
 */
function crud_modernbill_client_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init('select * from client_info', 'mb')
		->set_title('Modernbill Client List')
		->go();
}
