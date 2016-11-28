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
 * crud_whos_online()
 * @return void
 */
function crud_whos_online() {
	add_output(alert('TODO', 'Hide some of the fields, make it auto update'));
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select history_id, session_owner, account_lid,  unix_timestamp() - unix_timestamp(history_timestamp) as session_idle, history_type, session_ip, session_id, session_login from sessions, session_log, accounts where history_sid=session_id and history_owner=account_id and account_ima != 'services' and history_timestamp >= date_sub(now(), INTERVAL 3 HOUR)")
		->set_title('Whos Online')
		->go();
}
