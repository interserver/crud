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
 * crud_innertell_pending_orders()
 * @return void
 */
function crud_innertell_pending_orders() {
		Crud::init('SELECT
	servers.server_id,
	server_custid,
	server_ccname,
	server_exp,
	server_bankname,
	server_cc,
	server_hostname,
	server_root,
	server_dedicated_tag,
	server_custom_tag,
	servers.server_status,
	server_comment,
	server_initial_bill,
	server_hardware,
	server_ips,
	server_monthly_bill,
	server_setup,
	server_discount,
	server_rep,
	server_date,
	server_total_cost,
	server_referrer,
	server_location,
	server_fraud,
	server_mb_package_id,
	server_hardware_ordered,
	server_billed,
	server_welcome_email,
	server_dedicated_cpu,
	server_dedicated_memory,
	server_dedicated_hd1,
	server_dedicated_hd2,
	server_dedicated_bandwidth,
	server_dedicated_ips,
	server_dedicated_os,
	server_dedicated_cp,
	server_dedicated_raid,
	location.id AS server_location,
	dedicated_cpu.short_desc AS server_dedicated_cpu_desc,
	dedicated_cpu.monthly_price AS server_dedicated_cpu_cost,
	dedicated_memory.short_desc AS server_dedicated_memory_desc,
	dedicated_memory.monthly_price AS server_dedicated_memory_cost,
	dedicated_hd1.short_desc AS server_dedicated_hd1_desc,
	dedicated_hd1.monthly_price AS server_dedicated_hd1_cost,
	dedicated_hd2.short_desc AS server_dedicated_hd2_desc,
	dedicated_hd2.monthly_price AS server_dedicated_hd2_cost,
	dedicated_bandwidth.short_desc AS server_dedicated_bandwidth_desc,
	dedicated_bandwidth.monthly_price AS server_dedicated_bandwidth_cost,
	dedicated_ips.short_desc AS server_dedicated_ips_desc,
	dedicated_ips.monthly_price AS server_dedicated_ips_cost,
	dedicated_os.short_desc AS server_dedicated_os_desc,
	dedicated_os.monthly_price AS server_dedicated_os_cost,
	dedicated_cp.short_desc AS server_dedicated_cp_desc,
	dedicated_cp.monthly_price AS server_dedicated_cp_cost
FROM
	servers
		LEFT JOIN
	location ON location.order_id = servers.server_id
		LEFT JOIN
	dedicated_cpu ON server_dedicated_cpu = dedicated_cpu.id
		LEFT JOIN
	dedicated_memory ON server_dedicated_memory = dedicated_memory.id
		LEFT JOIN
	dedicated_hd AS dedicated_hd1 ON server_dedicated_hd1 = dedicated_hd1.id
		LEFT JOIN
	dedicated_hd AS dedicated_hd2 ON server_dedicated_hd2 = dedicated_hd2.id
		LEFT JOIN
	dedicated_bandwidth ON server_dedicated_bandwidth = dedicated_bandwidth.id
		LEFT JOIN
	dedicated_ips ON server_dedicated_ips = dedicated_ips.id
		LEFT JOIN
	dedicated_os ON server_dedicated_os = dedicated_os.id
		LEFT JOIN
	dedicated_cp ON server_dedicated_cp = dedicated_cp.id', 'servers')
		->set_title('Pending Dedicated Server Orders')
		->go();
}
