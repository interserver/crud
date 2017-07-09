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
 * crud_vlans()
 * @return void
 */
function crud_vlans() {
	add_output(alert('TODO', 'Get Adding a VLAN working well, maybe some totals/stats type bottom row'));
		Crud::init('select * from vlans', 'domains')
		->set_title('IP VLAN Manager')
		->go();
}
