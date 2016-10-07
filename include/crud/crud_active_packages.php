<?php
function crud_active_packages() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select '__TBLNAME__' as module, count(*) AS packages
 FROM
  __TABLE__
WHERE
  __TABLE__.__PREFIX___status = 'active'")
		->set_title("Active Packages")
		->go();
}
