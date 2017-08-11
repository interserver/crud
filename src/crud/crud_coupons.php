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
 * crud_coupons()
 * @return void
 */
function crud_coupons() {
	add_output(alert('TODO', 'Add Adding Coupons, and improve the display'));
		Crud::init('select id,customer,usable,applies,type,amount,name,onetime,account_lid from coupons left join accounts on account_id=customer')
		->set_title('Coupons')
		->go();
}
