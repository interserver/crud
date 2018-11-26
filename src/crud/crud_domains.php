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
 * crud_domains()
 * @return void
 */
function crud_domains()
{
	Crud::init('select * from domains', 'domains')
		->set_title(_('Domains'))
		->go();
}
