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
 * crud_paypal_history()
 * @return void
 */
function crud_paypal_history() {
		Crud::init('Get_PayPal_History')
		->set_title('Paypal History')
		->go();
}
