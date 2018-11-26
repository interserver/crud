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
 * crud_vps_masters()
 * @return void
 */
function crud_vps_masters()
{
	Crud::init('select * from vps_masters', 'vps')
		->set_title(_('VPS Host Servers'))
		->go();
}
