<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_user_session_activity()
 * @return false|void
 */
function crud_user_session_activity()
{
    // admin only: this lists every customer's rows (the table has no *_custid column for
    // limit_custid to filter on) and index.php/ajax.php reach any registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('list_all')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    // history_sid is the live session id of whoever made the row (admins included) and
    // would log anyone in as them, so it is masked like the other secret columns
    if (!in_array('history_sid', Crud::$secret_columns, true)) {
        Crud::$secret_columns[] = 'history_sid';
    }
    Crud::init("select session_log.history_id, accounts.account_id, accounts.account_lid, accounts.account_ima, session_log.history_sid, session_log.history_timestamp, session_log.history_owner, session_log.history_type, session_log.history_new_value FROM accounts INNER JOIN session_log ON accounts.account_id = session_log.history_creator WHERE accounts.account_ima <> 'services'")
        ->set_limit_custid_role('list_all')
        ->set_title(_('User Session Activity'))
        ->disable_edit()
        ->go();
}
