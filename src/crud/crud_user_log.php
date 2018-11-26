<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_user_log()
 * @return void
 */
function crud_user_log()
{
	Crud::init('select * from user_log')
		->set_title(_('User Log'))
		->go();
}
