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
 * crud_form_manager()
 * @return void
 */
function crud_form_manager() {
	function_requirements('class.crud');
	crud::init('select * from forms')
		->set_title('Form Manager')
		->go();
}
