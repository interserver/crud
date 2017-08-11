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
 * crud_modernbill_package_list()
 * @return void
 */
function crud_modernbill_package_list() {
		Crud::init(
		'select  package_type.pack_id
     , package_type.pack_name
     , client_package.pack_price
     , client_package.client_id
     , client_info.client_email
     , client_package.cp_comments
     , client_package.domain FROM
  client_info
LEFT JOIN client_package
ON client_package.client_id = client_info.client_id
LEFT JOIN package_type
ON client_package.pack_id = package_type.pack_id
WHERE
  cp_status = 2', 'mb')
		->set_title('Modernbill Package Listing')
		->go();
}
