<?php
function crud_innertell_pending_orders_new() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select orders.id
     , orders.username
     , orders.ccname
     , orders.exp
     , orders.bankname
     , orders.cc
     , orders.servername
     , orders.root
     , orders.dedicated_tag
     , orders.custom_tag
     , orders.status
     , orders.`comment`
     , orders.inital_bill
     , orders.hardware
     , orders.ips
     , orders.monthly_bill
     , orders.setup
     , orders.discount
     , orders.rep
     , orders.`date`
     , orders.total_cost
     , orders.referer
     , orders.server_id
     , orders.fraud
     , orders.modernbill_package_id
     , orders.hardware_ordered
     , orders.server_billed
     , orders.welcome_email
     , orders.dedicated_cpu
     , orders.dedicated_memory
     , orders.dedicated_hd1
     , orders.dedicated_hd2
     , orders.dedicated_bandwidth
     , orders.dedicated_ips
     , orders.dedicated_os
     , orders.dedicated_cp
     , orders.dedicated_raid
     , group_concat(DISTINCT vlans.vlans_networks SEPARATOR ':') AS vlans
     , group_concat(DISTINCT vlans.vlans_ports SEPARATOR ':') AS ports
     , users.id as user_id FROM
  innertell.orders
LEFT JOIN admin.vlans
ON vlans.vlans_comment LIKE concat('%', orders.servername)
LEFT JOIN users
ON orders.username=users.username
GROUP BY
  orders.username
", 'innertell')
		->set_title("Pending Server Orders")
		->go();
}
