<?php
function crud_monitoring_list() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_monitoring_data")
		->set_title("Monitored Systems")
		->go();
}
