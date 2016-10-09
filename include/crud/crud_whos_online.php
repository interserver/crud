<?php
function crud_whos_online() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select history_id, session_owner, account_lid,  unix_timestamp() - unix_timestamp(history_timestamp) as session_idle, history_type, session_ip, session_id, session_login from sessions, session_log, accounts where history_section='sessions' and history_sid=session_id and history_owner=account_id and account_ima != 'services' and history_timestamp >= date_sub(now(), INTERVAL 3 HOUR)")
		->set_title("Whos Online")
		->go();
}
