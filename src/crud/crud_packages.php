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
 * crud_packages()
 * @return void
 */
function crud_packages() {
		Crud::init("select '__TITLE__' as service, __TITLE_FIELD__ as package, __PREFIX___status as status, __PREFIX___id as id, '__PREFIX__' as prefix from __TABLE__ where __PREFIX___custid='__CUSTID__' and __PREFIX___status not in ('canceled','expired')")
		->set_title('Your Packages')
		->go();
}
