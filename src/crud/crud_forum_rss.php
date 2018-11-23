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
 * crud_forum_rss()
 * @return void
 */
function crud_forum_rss()
{
	Crud::init('get_forum_rss', 'default', 'function')
		->set_title('Latest Forum Posts')
		->go();
}
