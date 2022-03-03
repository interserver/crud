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
 * crud_history_log()
 * @return void
 */
function crud_history_log()
{
	Crud::init('select * from history_log')
		->set_title(_('History Log'))
		->go();
}
