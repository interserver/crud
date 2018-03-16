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
 * crud_licenses()
 * @return void
 */
function crud_licenses() {
		Crud::init('select * from licenses', 'licenses')
		->set_title('Licenses')
		->go();
}
