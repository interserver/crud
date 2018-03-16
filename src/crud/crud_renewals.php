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
 * crud_renewals()
 * @return void
 */
function crud_renewals() {
		Crud::init('get_renewals', 'default', 'function')
		->set_title('Renewals')
		->go();
}
