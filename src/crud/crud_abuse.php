<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use MyCrud\Crud;

/**
 * crud_abuse()
 * @return void
 */
function crud_abuse()
{
    $db = $GLOBALS['tf']->db;
    if ($GLOBALS['tf']->ima != 'admin') {
        $lid = $db->real_escape($GLOBALS['tf']->accounts->cross_reference($GLOBALS['tf']->session->account_id));
    } elseif (isset($GLOBALS['tf']->variables->request['lid'])) {
        $lid = $db->real_escape($GLOBALS['tf']->variables->request['lid']);
    }
    Crud::init("select * from abuse where abuse_status='pending'".(isset($lid) ? " and abuse_lid='{$lid}'" : ""))
        ->disable_edit()
        ->disable_delete()
        ->set_page_limit(10)
        ->set_title(_('Abuse'))
        ->add_row_button('none.abuse&id=%id%', _('Update'), 'primary', 'cog')
        ->go();
}
