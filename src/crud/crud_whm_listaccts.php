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
 * crud_whm_listaccts()
 * @return void
 */
function crud_whm_listaccts()
{
	Crud::init('whm_get_accounts', 'default', 'function')
		->set_title(_('Accounts List'))
		->go();
}
