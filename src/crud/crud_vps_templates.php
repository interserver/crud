<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

function decorate_vps_templates($field, $value) {
	global $templateTypes;
	if ($field == 'template_type')
		return $GLOBALS['templateTypes'][intval($value)];
	if  ($field == 'template_available')
		return intval($value) == 1 ? 'Yes' : 'No';
	return $value;
}


/**
 * crud_templates()
 * @return void
 */
function crud_vps_templates()
{
	global $templateTypes;
	$db = get_module_db('default');
	$templateTypes = [];
	$db->query("select st_id, st_name from service_types where st_module='vps'");
	while ($db->next_record(MYSQL_ASSOC))
		$templateTypes[intval($db->Record['st_id'])] = $db->Record['st_name'];
	$crud = Crud::init('select template_id, template_type, template_os, template_version, template_bits, template_file, template_available, template_name, template_dir from vps_templates')
		->set_title(_('Templates'))
		->add_filter('template_type', 'decorate_vps_templates', 'function')
		->add_filter('template_available', 'decorate_vps_templates', 'function')
		->enable_fluid_container();
	$crud->add_input_type_field('template_type', 'select', ['values' => array_keys($templateTypes), 'labels' => array_values($templateTypes), 'default' => false]);
	$crud->add_field_validations('template_type', [['in_array' => array_keys($templateTypes)]]);
	$crud
		->go();
	bdump($crud);
}
