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
    $crud = Crud::init('select * from cc_log' . (!is_null($custid) ? ' where cc_custid='.$custid : ''))
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
