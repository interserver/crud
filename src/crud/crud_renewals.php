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
use \MyCrud\Crud;

/**
 * crud_renewals()
 * @return void
 */
function crud_renewals() {
		Crud::init('get_renewals', 'default', 'function')
		->set_title('Renewals')
		->go();
}
