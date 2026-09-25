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
 * crud_vps()
 * @return void
 */
function crud_vps()
{
    // every column except vps_rootpass, which is only shown masked (search, sort and
    // edit of it are blocked by Crud's secret-column handling as well)
    Crud::init("select vps_id,vps_uuid,vps_custid,vps_server,vps_ip,vps_ipv6,vps_vzid,vps_currency,vps_type,vps_order_date,vps_status,vps_invoice,vps_coupon,vps_extra,vps_hostname,vps_server_status,vps_comment,vps_slices,vps_vnc,vps_vnc_port,if(ifnull(vps_rootpass,'')='','','********') as vps_rootpass,vps_mac,vps_os,vps_version,vps_location,vps_platform,vps_diskused,vps_diskmax from vps", 'vps')
        ->set_limit_custid_role('list_all')
        ->set_title(_('Virtual Private Servers'))
        ->go();
}
