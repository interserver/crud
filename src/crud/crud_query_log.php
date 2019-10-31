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
 * crud_query_log()
 * @return void
 */
function crud_query_log()
{
	function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
        dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        return false;
    }
	if (isset($GLOBALS['tf']->variables->request['custid']))
		$custid = $GLOBALS['tf']->variables->request['custid'];
	elseif (isset($GLOBALS['tf']->variables->request['customer']))
		$custid = $GLOBALS['tf']->variables->request['customer'];
	Crud::init('select * from query_log' . (isset($custid) ? ' where history_owner='.$custid : ''))
		->set_order('history_timestamp', 'desc')
        	->disable_delete()
	        ->disable_edit()
		->enable_fluid_container()
		->set_title(_('Query Log'))
		->go();
}
