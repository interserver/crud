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
 * crud_session_log()
 * @return void
 */
function crud_session_log()
{
	Crud::init('select * from session_log')
		->set_title('session log')
		->go();
}
