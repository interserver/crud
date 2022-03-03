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
 * crud_fantastico_list()
 * @return void
 */
function crud_fantastico_list()
{
	Crud::init('get_fantastico_list', 'licenses', 'function')
		->set_title(_('Fantastico License List'))
		->go();
}
