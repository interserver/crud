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
 * crud_session_log()
 * @return void
 */
function crud_session_log() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init('select * from session_log')
		->set_title('session log')
		->go();
}
