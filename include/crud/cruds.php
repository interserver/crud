<?php
/**
 * CRUD System
 * Last Changed: $LastChangedDate: 2016-10-05 12:42:23 -0400 (Wed, 05 Oct 2016) $
 * @author detain
 * @version $Revision: 21022 $
 * @copyright 2016
 * @package MyAdmin
 * @category Admin
 */

/**
 * cruds()
 * @return void
 */
function cruds() {
	add_js('bootstrap');
	page_title('CRUDs List');
	$functions = get_crud_funcs();
	$sizes = array();
	foreach ($functions as $level => $functions_arr)
		$sizes[$level] = sizeof($functions_arr);
	add_output("
<style type='text/css'>
.cruds.list-group {
	width: 90%;
	text-align: left;
}
.cruds.list-group .list-group-item {
	display: inline-table;
	margin: 2px;
	border-radius: 10px;
}
.cruds.list-group .list-group-item.active {
	display: block;
}
</style>");
	add_output("
<div class='cruds list-group'>
	<div class='list-group-item active'>
		CRUD Page Links
		<span class='pull-right label label-danger' style='margin-left: 10px;'>Not Working Yet ({$sizes['danger']})</span>
		<span class='pull-right label label-warning' style='margin-left: 10px;'>Loads ({$sizes['warning']})</span>
		<span class='pull-right label label-info' style='margin-left: 10px;'>Almost Done ({$sizes['info']})</span>
		<span class='pull-right label label-success' style='margin-left: 10px;'>Ready ({$sizes['success']})</span>
		<span class='pull-right'>Key:</span>
	</div>");
	foreach ($functions as $level => $functions_arr) {
		foreach ($functions_arr as $orig_function => $function_data) {
			add_output("
	<a href='?choice=none.{$function_data['function']}' class='list-group-item' target='_blank'>
		<span class='label label-{$level}'>{$orig_function}</span> - {$function_data['title']}
	</a>");
		}
	}
	add_output("
</div>
");
}

function get_crud_funcs() {
	$functions = array(
		'success' => array(
			'admins' => array('function' => 'admins', 'title' => 'Administrator Role Assignments'),
			'backups_list' => array('function' => 'crud_backups_list', 'title' => 'Backup List'),
			'domains_list' => array('function' => 'crud_domains_list', 'title' => 'Domains'),
			'ssl_list' => array('function' => 'crud_ssl_list', 'title' => 'SSL Certificates'),
			'vps_list' => array('function' => 'vps_list', 'title' => 'VPS List'),
		),
		'info' => array(
			'coupons' => array('function' => 'crud_coupons', 'title' => 'Coupons'),
			'customers' => array('function' => 'crud_customers', 'title' => 'Customers'),
			'last_logins' => array('function' => 'crud_last_logins', 'title' => 'Last Logn Locations'),
		),
		'warning' => array(
			'abuse' => array('function' => 'crud_abuse', 'title' => 'Abuse'),
			'active_packages' => array('function' => 'crud_active_packages', 'title' => 'Active Packages'),
			'backups' => array('function' => 'crud_backups', 'title' => 'Backups'),
			'dedicated_list' => array('function' => 'crud_dedicated_list', 'title' => 'Dedicated List'),
			'domains' => array('function' => 'crud_domains', 'title' => 'Domains'),
			'form_manager' => array('function' => 'crud_form_manager', 'title' => 'Form Manager'),
			'history_log' => array('function' => 'crud_history_log', 'title' => 'History Log'),
			'innertell_orders' => array('function' => 'crud_innertell_orders', 'title' => 'Dedicated Server Orders'),
			'innertell_search' => array('function' => 'crud_innertell_search', 'title' => 'Search Results'),
			'invoices' => array('function' => 'crud_invoices', 'title' => 'Invoices'),
			'licenses' => array('function' => 'crud_licenses', 'title' => 'Licenses'),
			'modernbill_client_list' => array('function' => 'crud_modernbill_client_list', 'title' => 'Modernbill Client List'),
			'modernbill_invoice_list' => array('function' => 'crud_modernbill_invoice_list', 'title' => 'Modernbill Invoice List'),
			'modernbill_package_list' => array('function' => 'crud_modernbill_package_list', 'title' => 'Modernbill Package Listing'),
			'month_payment_totals' => array('function' => 'crud_month_payment_totals', 'title' => 'Payments This Month'),
			'packages' => array('function' => 'crud_packages', 'title' => 'Your Packages'),
			'paypal_transactions' => array('function' => 'crud_paypal_transactions', 'title' => 'Paypal Transactions'),
			'repeat_invoices' => array('function' => 'crud_repeat_invoices', 'title' => 'Repeat Invoices'),
			'server_actions' => array('function' => 'crud_server_actions', 'title' => 'Recent Server Commands'),
			'session_log' => array('function' => 'crud_session_log', 'title' => 'session log'),
			'ssl' => array('function' => 'crud_ssl', 'title' => 'SSL Certificates'),
			'user_log' => array('function' => 'crud_user_log', 'title' => 'User Log'),
			'view_invoices' => array('function' => 'crud_view_invoices', 'title' => 'View Invoices List'),
			'view_invoices2' => array('function' => 'crud_view_invoices2', 'title' => 'View Invoices List'),
			'vps' => array('function' => 'crud_vps', 'title' => 'Virtual Private Servers'),
			'vps_masters' => array('function' => 'crud_vps_masters', 'title' => 'VPS Host Servers'),
			'whos_online' => array('function' => 'crud_whos_online', 'title' => 'Whos Online'),
		),
		'danger' => array(
			'admin_tickets' => array('function' => 'crud_admin_tickets', 'title' => 'Admin Tickets'),
			'admin_tickets_widget' => array('function' => 'crud_admin_tickets_widget', 'title' => 'Admin Tickets'),
			'dns_manager' => array('function' => 'crud_dns_manager', 'title' => 'DNS Manager'),
			'fantastico_list' => array('function' => 'crud_fantastico_list', 'title' => 'Fantastico License List'),
			'forum_rss' => array('function' => 'crud_forum_rss', 'title' => 'Latest Forum Posts'),
			'innertell_pending_orders' => array('function' => 'crud_innertell_pending_orders', 'title' => 'Pending Dedicated Server Orders'),
			'innertell_pending_orders_new' => array('function' => 'crud_innertell_pending_orders_new', 'title' => 'Pending Server Orders'),
			'licenses_list' => array('function' => 'crud_licenses_list', 'title' => 'License List'),
			'monitoring_list' => array('function' => 'crud_monitoring_list', 'title' => 'Monitored Systems'),
			'new_vps' => array('function' => 'crud_new_vps', 'title' => 'Newest VPS Signups'),
			'paypal_history' => array('function' => 'crud_paypal_history', 'title' => 'Paypal History'),
			'pending_vps_list' => array('function' => 'crud_pending_vps_list', 'title' => 'Pending Virtual Private Servers (VPS)'),
			'queue_log' => array('function' => 'crud_queue_log', 'title' => 'Queue Log'),
			'quickservers_list' => array('function' => 'crud_quickservers_list', 'title' => 'QuickServers'),
			'renewals' => array('function' => 'crud_renewals', 'title' => 'Renewals'),
			'reusable_fantastico' => array('function' => 'crud_reusable_fantastico', 'title' => 'Reusable Fantastico'),
			'server_billing_stats' => array('function' => 'crud_server_billing_stats', 'title' => 'Server Billing Stats'),
			'templates' => array('function' => 'crud_templates', 'title' => 'Templates'),
			'user_session_activity' => array('function' => 'crud_user_session_activity', 'title' => 'User Session Activity'),
			'vlans' => array('function' => 'crud_vlans', 'title' => 'IP VLAN Manager'),
			'vps_bandwidth' => array('function' => 'crud_vps_bandwidth', 'title' => 'VPS Bandwidth'),
			'vps_ips' => array('function' => 'crud_vps_ips', 'title' => 'VPS IP Adddress Space'),
			'vps_list_free_ips2' => array('function' => 'crud_vps_list_free_ips2', 'title' => 'Free/Available IPs For VPs Servers'),
			'vps_list_free_ips' => array('function' => 'crud_vps_list_free_ips', 'title' => 'Free/Available IPs For VPs Servers'),
			'vps_next_servers' => array('function' => 'crud_vps_next_servers', 'title' => 'VPS Next Setup Servers'),
			'webhosting_list' => array('function' => 'crud_webhosting_list', 'title' => 'Website List'),
			'whm_listaccts' => array('function' => 'crud_whm_listaccts', 'title' => 'Accounts List'),
		),
	);
	return $functions;
}
