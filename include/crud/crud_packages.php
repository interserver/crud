<?php
function crud_packages() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select '__TITLE__' as service, __TITLE_FIELD__ as package, __PREFIX___status as status, __PREFIX___id as id, '__PREFIX__' as prefix from __TABLE__ where __PREFIX___custid='__CUSTID__' and __PREFIX___status not in ('canceled','expired')")
		->set_title("Your Packages")
		->go();
}