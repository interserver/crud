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
use \detain\Crud;

/**
 * crud_domains()
 * @return void
 */
function crud_domains() {
	crud::init('select * from domains', 'domains')
		->set_title('Domains')
		->go();
}
