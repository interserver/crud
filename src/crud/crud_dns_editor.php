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
 * crud_dns_editor()
 * @return void
 */
function crud_dns_editor()
{
	function_requirements('class.Crud');
	$domain_id = (int)$GLOBALS['tf']->variables->request['id'];
	Crud::init("select * from records where domain_id='{$domain_id}'", 'pdns')
		->set_title('DNS Editor')
		->enable_fluid_container()
		->set_extra_url_args('&id='.$domain_id)
		->go();
}
