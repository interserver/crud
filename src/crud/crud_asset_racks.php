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
 * crud_abuse()
 * @return void
 */
function crud_asset_racks() {
	Crud::init('select * from asset_racks')
	->set_title('Asset Racks')
	->go();
}
