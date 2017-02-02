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
use \detain\Crud\Crud;

/**
 * crud_innertell_pending_orders()
 * @return void
 */
function crud_innertell_pending_orders() {
		Crud::init('select orders.id, username, ccname, exp, bankname, cc, servername, root, dedicated_tag, custom_tag, orders.status, comment, inital_bill, hardware, ips, monthly_bill, setup, discount, rep, date, total_cost, referer, server_id, fraud, modernbill_package_id, hardware_ordered, server_billed, welcome_email, dedicated_cpu, dedicated_memory, dedicated_hd1, dedicated_hd2, dedicated_bandwidth, dedicated_ips, dedicated_os, dedicated_cp, dedicated_raid, location.id as server_id, dedicated_cpu.short_desc as dedicated_cpu_desc, dedicated_cpu.monthly_price as dedicated_cpu_cost, dedicated_memory.short_desc as dedicated_memory_desc, dedicated_memory.monthly_price as dedicated_memory_cost, dedicated_hd1.short_desc as dedicated_hd1_desc, dedicated_hd1.monthly_price as dedicated_hd1_cost, dedicated_hd2.short_desc as dedicated_hd2_desc, dedicated_hd2.monthly_price as dedicated_hd2_cost, dedicated_bandwidth.short_desc as dedicated_bandwidth_desc, dedicated_bandwidth.monthly_price as dedicated_bandwidth_cost, dedicated_ips.short_desc as dedicated_ips_desc, dedicated_ips.monthly_price as dedicated_ips_cost, dedicated_os.short_desc as dedicated_os_desc, dedicated_os.monthly_price as dedicated_os_cost, dedicated_cp.short_desc as dedicated_cp_desc, dedicated_cp.monthly_price as dedicated_cp_cost  from orders left join location on location.order_id=orders.id left join dedicated_cpu on dedicated_cpu=dedicated_cpu.id  left join dedicated_memory on dedicated_memory=dedicated_memory.id  left join dedicated_hd as dedicated_hd1 on dedicated_hd1=dedicated_hd1.id  left join dedicated_hd as dedicated_hd2 on dedicated_hd2=dedicated_hd2.id  left join dedicated_bandwidth on dedicated_bandwidth=dedicated_bandwidth.id  left join dedicated_ips on dedicated_ips=dedicated_ips.id  left join dedicated_os on dedicated_os=dedicated_os.id  left join dedicated_cp on dedicated_cp=dedicated_cp.id', 'innertell')
		->set_title('Pending Dedicated Server Orders')
		->go();
}
