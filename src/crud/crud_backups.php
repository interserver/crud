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
 * crud_backups()
 * @return void
 */
function crud_backups()
{
	Crud::init('select * from backups', 'backups')
		->set_title('Backups')
		->go();
}
