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
 * crud_vlans()
 * @return void
 */
function crud_vlans()
{
    add_output(alert('TODO', 'Get Adding a VLAN working well, maybe some totals/stats type bottom row'));
    Crud::init('select * from vlans', 'domains')
        ->set_title(_('IP VLAN Manager'))
        ->go();
}
