<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2025
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_domains()
 * @return void
 */
function crud_domains()
{
    // every column except domain_password, which is only shown masked (search, sort and
    // edit of it are blocked by Crud's secret-column handling as well)
    Crud::init("select domain_id,domain_uuid,domain_hostname,domain_username,if(ifnull(domain_password,'')='','','********') as domain_password,domain_type,domain_currency,domain_expire_date,domain_order_date,domain_custid,domain_status,domain_invoice,domain_coupon,domain_firstname,domain_lastname,domain_email,domain_address,domain_address2,domain_address3,domain_city,domain_state,domain_zip,domain_country,domain_phone,domain_fax,domain_company,domain_premium,domain_extra from domains", 'domains')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Domains'))
        ->go();
}
