<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

function request_log_decorate($field, $value) {
	$new = json_decode($value, true);
	if (is_null($new)) {
		return $value;
	}
	$new = json_encode($new, JSON_PRETTY_PRINT);
	$new = '<pre style="text-align: left;">'.$new.'</pre>';
	return $new;
}

/**
 * @return void
 */
function crud_request_log($custid = null, $return_output = false)
{
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
		if (is_null($custid)) {
			dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
		}
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
	$crud = Crud::init('select request_timestamp'.(is_null($custid) ? ', request_custid' : '').', request_service, request_function, request_category, request_action, request_request, request_result from request_log' . (!is_null($custid) ? ' where request_custid='.$custid : ''))
        ->set_limit_custid_role('list_all')
		->set_order('request_timestamp', 'desc')
		->set_use_html_filtering(false)
		->set_return_output($return_output)
		->disable_delete()
		->disable_edit()
		->add_filter('request_request', 'request_log_decorate', 'function')
		->add_filter('request_result', 'request_log_decorate', 'function')
		->enable_fluid_container()
		->set_title(_('Request Log'));
	$return = $crud->go();
	if ($return_output == true) {
		return $return;
	}
}
