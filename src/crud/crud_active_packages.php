<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_active_packages()
 * @return void
 */
function crud_active_packages()
{
	Crud::init("select '__TBLNAME__' as module, count(*) AS packages
 FROM
  __TABLE__
WHERE
  __TABLE__.__PREFIX___status = 'active'")
		->set_title('Active Packages')
		->go();
}
