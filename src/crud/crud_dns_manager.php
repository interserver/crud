<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_dns_manager()
 * @return void
 */
function crud_dns_manager() {
	add_output(alert('TODO', 'Add Domain and Multiple Domains buttons, and Add Link to DNS Editor'));
		Crud::init("select domains.id, domains.account, domains.name, records.content from domains left join records on domains.id=records.domain_id and records.type='A' and (records.name=domains.name or records.name='')", 'pdns')
		->set_title('DNS Manager')
		->go();
}
