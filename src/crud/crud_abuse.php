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
 * crud_abuse()
 * @return void
 */
function crud_abuse()
{
	Crud::init('select * from abuse')
		->set_title(_('Abuse'))
		->go();
}
