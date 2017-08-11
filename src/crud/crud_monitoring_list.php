<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_monitoring_list()
 * @return void
 */
function crud_monitoring_list() {
		Crud::init('get_monitoring_data', 'default', 'function')
		->set_title('Monitored Systems')
		->go();
}
