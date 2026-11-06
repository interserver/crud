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
 * crud_whos_online()
 * @return false|void
 */
function crud_whos_online()
{
    // admin only: this lists every customer's rows (the table has no *_custid column for
    // limit_custid to filter on) and index.php/ajax.php reach any registered function
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('list_all')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
    add_output(alert('TODO', 'Hide some of the fields, make it auto update'));
    Crud::init("SELECT history_owner custid, account_lid AS email, DATE_FORMAT(history_timestamp, '%r') AS time, history_type AS page, access_ip AS ip
FROM session_log
	LEFT JOIN access_log ON history_sid = access_sid
	LEFT JOIN sessions ON history_sid = session_id
	LEFT JOIN accounts ON history_owner = account_id
WHERE session_id IS NOT NULL
AND account_id IS NOT NULL
AND access_id IS NOT NULL")
        ->set_order('history_id', 'desc')
        ->set_title(_('Whos Online'))
        ->disable_edit()
        ->go();
}
