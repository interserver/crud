<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2018
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * @return void
 */
function crud_asset_locations() {
	\MyCrud\Crud\Crud::init('select * from asset_locations')
	->set_title('Asset Locations')
	->go();
}
