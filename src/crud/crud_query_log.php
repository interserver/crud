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
 * crud_query_log()
 * @return void
 */
function crud_query_log($custid = null, $return_output = false)
{
    function_requirements('has_acl');
    if (\MyAdmin\App::ima() != 'admin' || !has_acl('client_billing')) {
        if (is_null($custid)) {
            dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        }
        return false;
    }
    // custid / customer may be an account uuid rather than an account id, so
    // resolve it (0 for unknown) and always cast before it goes into the sql
    if (isset(\MyAdmin\App::variables()->request['custid'])) {
        $custid = resolve_account_id(\MyAdmin\App::variables()->request['custid']);
    } elseif (isset(\MyAdmin\App::variables()->request['customer'])) {
        $custid = resolve_account_id(\MyAdmin\App::variables()->request['customer']);
    } elseif (!is_null($custid)) {
        $custid = (int)$custid;
    }
    $crud = Crud::init('select * from query_log' . (!is_null($custid) ? ' where history_owner='.$custid : ''))
        ->set_order('history_timestamp', 'desc')
        ->set_return_output($return_output)
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->set_page_limit(500)
        ->set_title(_('Query Log'));
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
