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
 * crud_licenses()
 * @return void
 */
function crud_licenses()
{
	Crud::init('select * from licenses', 'licenses')
		->set_title(_('Licenses'))
		->go();
}
