<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_user_session_activity()
 * @return void
 */
function crud_user_session_activity()
{
	Crud::init("select session_log.history_id, accounts.account_id, accounts.account_lid, accounts.account_ima, session_log.history_sid, session_log.history_timestamp, session_log.history_owner, session_log.history_type, session_log.history_new_value FROM accounts INNER JOIN session_log ON accounts.account_id = session_log.history_creator WHERE accounts.account_ima <> 'services'")
		->set_title(_('User Session Activity'))
		->go();
}
