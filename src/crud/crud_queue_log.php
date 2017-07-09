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
 * crud_queue_log()
 * @return void
 */
function crud_queue_log() {
		Crud::init('select * from queue_log')
		->set_title('Queue Log')
		->go();
}
