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
 * pretty prints a logged api request/response that is json, html escaped.
 *
 * the stored responses carry text the far end chose (an ses bounce's diagnosticCode is the
 * rejecting mail server's own reply), and json_encode() does not escape < or >, so the
 * printed json is escaped. the crud has html filtering on and hands this filter the value
 * already escaped by htmlspecial() (ENT_COMPAT | ENT_HTML5), which json_decode() cannot read:
 * that escaping is undone first, the json decoded and pretty printed as before, and the result
 * escaped again for the page. a value that is not json comes back escaped.
 *
 * @param string $field
 * @param string|null $value the htmlspecial() escaped column value
 * @return string html
 */
function request_log_decorate($field, $value)
{
    $value = htmlspecialchars_decode((string) $value, ENT_COMPAT | ENT_HTML5);
    $new = json_decode($value, true);
    if (is_null($new)) {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    $new = json_encode($new, JSON_PRETTY_PRINT);
    $new = '<pre style="text-align: left;">'.htmlspecialchars((string) $new, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'</pre>';
    return $new;
}

/**
 * @return void
 */
function crud_request_log($custid = null, $return_output = false)
{
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
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
    // custid / customer may be an account uuid rather than an account id, so
    // resolve it (0 for unknown) and always cast before it goes into the sql
    if (isset(\MyAdmin\App::variables()->request['custid'])) {
        $custid = resolve_account_id(\MyAdmin\App::variables()->request['custid']);
    } elseif (isset(\MyAdmin\App::variables()->request['customer'])) {
        $custid = resolve_account_id(\MyAdmin\App::variables()->request['customer']);
    } elseif (!is_null($custid)) {
        $custid = (int)$custid;
    }
    $crud = Crud::init('select request_timestamp'.(is_null($custid) ? ', request_custid' : '').', request_service, request_function, request_category, request_action, request_request, request_result from request_log' . (!is_null($custid) ? ' where request_custid='.$custid : ''))
        // the list of every customer's requests is limited to the admin's own account without
        // list_all. one customer's log (edit_customer embeds it with that customer's id, or the
        // request names the customer) is already filtered to that customer by the where above,
        // like core's legacy forms let an explicit custid win over list_all_acl; the role would
        // AND the admin's own id onto it and empty the tab for every admin without list_all
        ->set_limit_custid_role(is_null($custid) ? 'list_all' : false)
        ->set_order('request_timestamp', 'desc')
        // escaped like every list: request_log_decorate() reads the escaped value and escapes
        // what it prints (filtering was off so the decorator could json_decode the raw value,
        // which printed the logged responses as html)
        ->set_use_html_filtering(true)
        ->set_return_output($return_output)
        ->disable_delete()
        ->disable_edit()
        ->add_filter('request_request', 'request_log_decorate', 'function')
        ->add_filter('request_result', 'request_log_decorate', 'function')
        ->enable_fluid_container()
        ->set_page_limit(500)
        ->set_title(_('Request Log'));
    // edit_customer embeds this tab (with the customer's id, its url naming them as customer=).
    // the crud urls (crud.js's ajax refills: paging, search, export) named the page that was
    // dispatched, edit_customer, and carried no customer, so a refill ran the whole
    // edit_customer page with none ('Invalid Customer'). they name this function and the
    // customer it resolved, so a refill (which calls it with no arguments) lists the same
    // customer; get_extra_url_args() does not add a second custid
    $crud->choice = __FUNCTION__;
    if (!is_null($custid)) {
        $crud->set_extra_url_args('&custid='.(int) $custid);
    }
    $return = $crud->go();
    if ($return_output == true) {
        return $return;
    }
}
