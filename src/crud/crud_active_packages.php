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
 * crud_active_packages()
 * @return void
 */
function crud_active_packages()
{
    Crud::init("select '__TBLNAME__' as module, count(*) AS packages
 FROM
  __TABLE__
WHERE
  __TABLE__.__PREFIX___status = 'active'")
        ->set_title(_('Active Packages'))
        // list only: its edit never wrote anything (the written table had no query_where entry,
        // so the handler fataled); with that fixed it would be a raw editor of the table
        ->disable_edit()
        ->go();
}
