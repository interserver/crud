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
 * crud_queue_log()
 * @return void
 */
function crud_queue_log()
{
	Crud::init('select * from queue_log')
		->set_title(_('Queue Log'))
		->go();
}
