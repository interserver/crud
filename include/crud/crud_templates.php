<?php
function crud_templates() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select  get_template_files")
		->set_title("Templates")
		->go();
}
