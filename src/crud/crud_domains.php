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
 * crud_domains()
 * @return void
 */
function crud_domains() {
		Crud::init('select * from domains', 'domains')
		->set_title('Domains')
		->go();
}
