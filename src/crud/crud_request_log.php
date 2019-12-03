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
 * @return void
 */
function crud_request_log($custid = null)
{
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
		dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
		return false;
	}
/*
	   request_id: 1
   request_module: webhosting
request_timestamp: 2015-10-06 00:16:44
   request_custid: 267883
 request_function: website_create
 request_category: cpanel
   request_action: createacct
  request_request: {"ip":"n","cgi":1,"frontpage":0,"hasshell":0,"cpmod":"x3","maxsql":"unlimited","maxpop":"unlimited","maxlst":0,"maxsub":"unlimited","quota":"unlimited","maxftp":"unlimited","maxpark":"unlimited","maxaddon":"unlimited","bwlimit":"unlimited","domain":"k-botfb.net","username":"kbotfbne","password":"matkhau123","contactemail":"vunguyenkhanhv1.6599@gmail.com"}
   request_result: {"result":[{"options":null,"statusmsg":"A DNS entry for â€œk-botfb.netâ€ already exists. You must remove this DNS entry from all servers in the DNS cluster to proceed.","rawout":null,"status":0}]}
*/	
	if (isset($GLOBALS['tf']->variables->request['custid']))
		$custid = $GLOBALS['tf']->variables->request['custid'];
	elseif (isset($GLOBALS['tf']->variables->request['customer']))
		$custid = $GLOBALS['tf']->variables->request['customer'];
	Crud::init('select * from request_log' . (!is_null($custid) ? ' where request_custid='.$custid : ''))
		->set_order('request_timestamp', 'desc')
			->disable_delete()
			->disable_edit()
		->enable_fluid_container()
		->set_title(_('Request Log'))
		->go();
}
