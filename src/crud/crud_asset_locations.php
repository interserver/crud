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
 * @return void
 */
function crud_asset_locations()
{
    Crud::init('select * from asset_locations')
    ->set_title(_('Asset Locations'))
    ->go();
}
