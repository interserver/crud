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
 * crud_coupons()
 * @return void
 */
function crud_coupons() {
	add_output(alert('TODO', 'Add Adding Coupons, and improve the display'));
	function_requirements('class.crud');
	crud::init('select id,customer,usable,applies,type,amount,name,onetime,account_lid from coupons left join accounts on account_id=customer')
		->set_title('Coupons')
		->go();
}
