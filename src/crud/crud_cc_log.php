<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;


function decorate_cc($field, $value) {
    return (mask_cc($value));
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
    if (isset(\MyAdmin\App::variables()->request['custid'])) {
        $custid = \MyAdmin\App::variables()->request['custid'];
    } elseif (isset(\MyAdmin\App::variables()->request['customer'])) {
        $custid = \MyAdmin\App::variables()->request['customer'];
    }
    $crud = Crud::init('select 
cc_id, cc_timestamp, cc_custid, cc_request_type, cc_request_version, cc_request_email, cc_request_card_num, cc_request_exp_date, cc_request_card_code, 
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
    $return = $crud->go();
    if ($return_output == true) {
        return $return;
    }
}
