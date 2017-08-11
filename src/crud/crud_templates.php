<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_templates()
 * @return void
 */
function crud_templates() {
		Crud::init('get_template_files', 'default', 'function')
		->set_title('Templates')
		->go();
}
