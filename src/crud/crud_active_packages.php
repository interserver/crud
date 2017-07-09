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
 * crud_active_packages()
 * @return void
 */
function crud_active_packages() {
		Crud::init("select '__TBLNAME__' as module, count(*) AS packages
 FROM
  __TABLE__
WHERE
  __TABLE__.__PREFIX___status = 'active'")
		->set_title('Active Packages')
		->go();
}
