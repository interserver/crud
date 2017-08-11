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
 * crud_modernbill_client_list()
 * @return void
 */
function crud_modernbill_client_list() {
		Crud::init('select * from client_info', 'mb')
		->set_title('Modernbill Client List')
		->go();
}
