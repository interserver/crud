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
 * crud_vlans()
 * @return void
 */
function crud_vlans() {
	add_output(alert('TODO', 'Get Adding a VLAN working well, maybe some totals/stats type bottom row'));
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init('select * from vlans', 'admin')
		->set_title('IP VLAN Manager')
		->go();
}