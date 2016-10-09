<?php
function crud_user_session_activity() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select      session_log.history_id
     , accounts.account_id
     , accounts.account_lid
     , accounts.account_ima
     , session_log.history_sid
     , session_log.history_timestamp
     , session_log.history_owner
     , session_log.history_type
     , session_log.history_new_value
 FROM
  accounts
INNER JOIN session_log
ON accounts.account_id = session_log.history_creator
WHERE
  accounts.account_ima <> 'services'
  AND session_log.history_section = 'sessions'
")
		->set_title("User Session Activity")
		->go();
}
