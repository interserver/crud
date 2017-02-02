<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_fantastico_list()
 * @return void
 */
function crud_fantastico_list() {
	function_requirements('class.crud');
	crud::init('get_fantastico_list', 'licenses', 'function')
		->set_title('Fantastico License List')
		->go();
}
