<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2017
 * @package MyAdmin
 * @category Admin
 */

/**
 * crud_vps_bandwidth()
 * @return void
 */
function crud_vps_bandwidth() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select vps.vps_hostname AS host, inet_ntoa(bandwidth.ip) AS ip, vps_masters.vps_name AS master, sum(bandwidth.`in`) AS `in`, sum(bandwidth.`out`) AS `out`, sum(bandwidth.`in`) + sum(bandwidth.`out`) AS total, date_format(bandwidth.`when`, '%Y-%m-%d %H') AS datehour, sum(bandwidth.`in`) / (unix_timestamp(max(bandwidth.`when`)) - unix_timestamp(min(bandwidth.`when`))) AS inrate, sum(bandwidth.`out`) / (unix_timestamp(max(bandwidth.`when`)) - unix_timestamp(min(bandwidth.`when`))) AS outrate, (sum(bandwidth.`in`) + sum(bandwidth.`out`)) / (unix_timestamp(max(bandwidth.`when`)) - unix_timestamp(min(bandwidth.`when`))) AS totalrate FROM bandwidth LEFT OUTER JOIN vps_masters ON bandwidth.server = vps_masters.vps_id LEFT OUTER JOIN vps ON bandwidth.vps = vps.vps_id WHERE day(bandwidth.`when`) = day(now()) GROUP BY bandwidth.ip, hour(bandwidth.`when`) ORDER BY date_format(bandwidth.`when`, '%Y-%m-%d %H') DESC,  sum(bandwidth.`in`) + sum(bandwidth.`out`) DESC", 'vps')
		->set_title('VPS Bandwidth')
		->go();
}
