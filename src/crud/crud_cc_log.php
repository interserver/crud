<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_cc_log()
 * @return void
 */
function crud_cc_log($custid = null, $return_output = false)
{
    function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('client_billing')) {
        if (is_null($custid)) {
            dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
        }
        return false;
    }
    if (isset($GLOBALS['tf']->variables->request['custid'])) {
        $custid = $GLOBALS['tf']->variables->request['custid'];
    } elseif (isset($GLOBALS['tf']->variables->request['customer'])) {
        $custid = $GLOBALS['tf']->variables->request['customer'];
    }
    $crud = Crud::init('select * from cc_log' . (!is_null($custid) ? ' where cc_custid='.$custid : ''))
        ->set_order('cc_timestamp', 'desc')
        ->set_return_output($return_output)
        ->disable_delete()
        ->disable_edit()
        ->enable_fluid_container()
        ->set_title(_('CC Log'));
    $return = $crud->go();
    if ($return_output == true) {
        return $return;
    }
}
