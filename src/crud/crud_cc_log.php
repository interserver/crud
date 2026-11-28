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
 * the card number column's cell: mask_cc() of the value (the sql already masked it). a card
 * number that is NULL (a request logged without one) goes in as '', which mask_cc() gives
 * back as it is: the cell shows nothing, as it did, without mb_strlen()'s php 8.1 deprecation
 * for NULL on every such row (the ajax list runs this filter on every refill now)
 *
 * @param string $field
 * @param string|null $value
 * @return string
 */
function decorate_cc($field, $value) {
    return (mask_cc((string) $value));
}

/**
 * crud_cc_log()
 * @return void
 */
function crud_cc_log($custid = null, $return_output = false)
{
    function_requirements('has_acl');
    function_requirements('mask_cc');
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
    // the card number is masked in the sql (mask_cc()'s stars plus the last 4, a value of 6
    // characters or less as it is), so the full number never reaches php for this page: the
    // decorate_cc cell filter only masked the table cells, while the inline crud_rows json, the
    // ajax list and every export carried it whole. it is a Crud secret column too, so it can
    // not be searched or sorted (searching the log by card number no longer matches).
    $crud = Crud::init('select
cc_id, cc_timestamp, cc_custid, cc_request_type, cc_request_version, cc_request_email, if(char_length(ifnull(cc_request_card_num,\'\'))>6,concat(repeat(\'*\',char_length(cc_request_card_num)-4),right(cc_request_card_num,4)),cc_request_card_num) as cc_request_card_num, cc_request_exp_date, if(ifnull(cc_request_card_code,\'\')=\'\',\'\',\'********\') as cc_request_card_code,
cc_result_code, cc_result_subcode, cc_result_reason_code, cc_result_reason_text, cc_result_auth_code, cc_result_avs_code, cc_result_trans_id, 
cc_request_invoice_num, cc_request_description, cc_request_amount, cc_request_cust_id, cc_request_first_name, cc_request_last_name, cc_request_company, cc_request_address, cc_request_city, cc_request_state, cc_request_zip, cc_request_country, cc_request_phone, 
cc_result_invoice_num, cc_result_description, cc_result_amount, cc_result_method, cc_result_customer_id, cc_result_trans_type, cc_result_first_name, cc_result_last_name, cc_result_company, cc_result_address, cc_result_city, cc_result_state, cc_result_zip, cc_result_country, cc_result_phone, cc_result_fax, cc_result_email, cc_result_shipto_last_name, cc_result_shipto_first_name, cc_result_shipto_company, cc_result_shipto_address, cc_result_shipto_city, cc_result_shipto_state, cc_result_shipto_zip, cc_result_shipto_country, cc_result_tax, cc_result_duty, cc_result_freight, cc_result_tax_exempt, cc_result_purchase_order_num, cc_result_md5, cc_result_card_code, cc_result_card_verification, cc_result_account_num, cc_result_card_type, cc_request_duplicate_window
from cc_log' . (!is_null($custid) ? ' where cc_custid='.$custid : ''))
        ->set_order('cc_timestamp', 'desc')
        ->set_return_output($return_output)
        ->add_filter('cc_request_card_num', 'decorate_cc', 'function')
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->set_page_limit(500)
        ->set_title(_('CC Log'));
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
