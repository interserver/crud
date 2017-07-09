<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_forum_rss()
 * @return void
 */
function crud_forum_rss() {
		Crud::init('get_forum_rss', 'default', 'function')
		->set_title('Latest Forum Posts')
		->go();
}
