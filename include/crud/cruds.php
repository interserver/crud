<?php
function cruds() {
	add_js('bootstrap');
	add_output("<div class='list-group' style='width: 500px; text-align: left;'>
<a href='#' class='list-group-item active'>CRUD Page Links</a>
<a href='?choice=none.crud_abuse' class='list-group-item'>
<span class='label label-info'>abuse</span> - Abuse</a>
<a href='?choice=none.crud_active_packages' class='list-group-item'>
<span class='label label-info'>active_packages</span> - Active Packages</a>
<a href='?choice=none.crud_admin_tickets' class='list-group-item'>
<span class='label label-info'>admin_tickets</span> - Admin Tickets</a>
<a href='?choice=none.crud_admin_tickets_widget' class='list-group-item'>
<span class='label label-info'>admin_tickets_widget</span> - Admin Tickets</a>
<a href='?choice=none.crud_backups' class='list-group-item'>
<span class='label label-info'>backups</span> - Backups</a>
<a href='?choice=none.crud_backups_list' class='list-group-item'>
<span class='label label-info'>backups_list</span> - Backup List</a>
<a href='?choice=none.crud_coupons' class='list-group-item'>
<span class='label label-info'>coupons</span> - Coupons</a>
<a href='?choice=none.crud_customers' class='list-group-item'>
<span class='label label-info'>customers</span> - Customers</a>
<a href='?choice=none.crud_dedicated_list' class='list-group-item'>
<span class='label label-info'>dedicated_list</span> - Dedicated List</a>
<a href='?choice=none.crud_dns_manager' class='list-group-item'>
<span class='label label-info'>dns_manager</span> - DNS Manager</a>
<a href='?choice=none.crud_domains' class='list-group-item'>
<span class='label label-info'>domains</span> - Domains</a>
<a href='?choice=none.crud_domains_list' class='list-group-item'>
<span class='label label-info'>domains_list</span> - Domains</a>
<a href='?choice=none.crud_fantastico_list' class='list-group-item'>
<span class='label label-info'>fantastico_list</span> - Fantastico License List</a>
<a href='?choice=none.crud_form_manager' class='list-group-item'>
<span class='label label-info'>form_manager</span> - Form Manager</a>
<a href='?choice=none.crud_forum_rss' class='list-group-item'>
<span class='label label-info'>forum_rss</span> - Latest Forum Posts</a>
<a href='?choice=none.crud_history_log' class='list-group-item'>
<span class='label label-info'>history_log</span> - History Log</a>
<a href='?choice=none.crud_innertell_orders' class='list-group-item'>
<span class='label label-info'>innertell_orders</span> - Dedicated Server Orders</a>
<a href='?choice=none.crud_innertell_pending_orders' class='list-group-item'>
<span class='label label-info'>innertell_pending_orders</span> - Pending Dedicated Server Orders</a>
<a href='?choice=none.crud_innertell_pending_orders_new' class='list-group-item'>
<span class='label label-info'>innertell_pending_orders_new</span> - Pending Server Orders</a>
<a href='?choice=none.crud_innertell_search' class='list-group-item'>
<span class='label label-info'>innertell_search</span> - Search Results</a>
<a href='?choice=none.crud_invoices' class='list-group-item'>
<span class='label label-info'>invoices</span> - Invoices</a>
<a href='?choice=none.crud_last_logins' class='list-group-item'>
<span class='label label-info'>last_logins</span> - Last Logn Locations</a>
<a href='?choice=none.crud_licenses' class='list-group-item'>
<span class='label label-info'>licenses</span> - Licenses</a>
<a href='?choice=none.crud_licenses_list' class='list-group-item'>
<span class='label label-info'>licenses_list</span> - License List</a>
<a href='?choice=none.crud_modernbill_client_list' class='list-group-item'>
<span class='label label-info'>modernbill_client_list</span> - Modernbill Client List</a>
<a href='?choice=none.crud_modernbill_invoice_list' class='list-group-item'>
<span class='label label-info'>modernbill_invoice_list</span> - Modernbill Invoice List</a>
<a href='?choice=none.crud_modernbill_package_list' class='list-group-item'>
<span class='label label-info'>modernbill_package_list</span> - Modernbill Package Listing</a>
<a href='?choice=none.crud_monitoring_list' class='list-group-item'>
<span class='label label-info'>monitoring_list</span> - Monitored Systems</a>
<a href='?choice=none.crud_month_payment_totals' class='list-group-item'>
<span class='label label-info'>month_payment_totals</span> - Payments This Month</a>
<a href='?choice=none.crud_new_vps' class='list-group-item'>
<span class='label label-info'>new_vps</span> - Newest VPS Signups</a>
<a href='?choice=none.crud_packages' class='list-group-item'>
<span class='label label-info'>packages</span> - Your Packages</a>
<a href='?choice=none.crud_paypal_history' class='list-group-item'>
<span class='label label-info'>paypal_history</span> - Paypal History</a>
<a href='?choice=none.crud_paypal_transactions' class='list-group-item'>
<span class='label label-info'>paypal_transactions</span> - Paypal Transactions</a>
<a href='?choice=none.crud_pending_vps_list' class='list-group-item'>
<span class='label label-info'>pending_vps_list</span> - Pending Virtual Private Servers (VPS)</a>
<a href='?choice=none.crud_queue_log' class='list-group-item'>
<span class='label label-info'>queue_log</span> - Queue Log</a>
<a href='?choice=none.crud_quickservers_list' class='list-group-item'>
<span class='label label-info'>quickservers_list</span> - QuickServers</a>
<a href='?choice=none.crud_renewals' class='list-group-item'>
<span class='label label-info'>renewals</span> - Renewals</a>
<a href='?choice=none.crud_repeat_invoices' class='list-group-item'>
<span class='label label-info'>repeat_invoices</span> - Repeat Invoices</a>
<a href='?choice=none.crud_reusable_fantastico' class='list-group-item'>
<span class='label label-info'>reusable_fantastico</span> - Reusable Fantastico</a>
<a href='?choice=none.crud_server_actions' class='list-group-item'>
<span class='label label-info'>server_actions</span> - Recent Server Commands</a>
<a href='?choice=none.crud_server_billing_stats' class='list-group-item'>
<span class='label label-info'>server_billing_stats</span> - Server Billing Stats</a>
<a href='?choice=none.crud_session_log' class='list-group-item'>
<span class='label label-info'>session_log</span> - session log</a>
<a href='?choice=none.crud_ssl' class='list-group-item'>
<span class='label label-info'>ssl</span> - SSL Certificates</a>
<a href='?choice=none.crud_ssl_list' class='list-group-item'>
<span class='label label-info'>ssl_list</span> - SSL Certificates</a>
<a href='?choice=none.crud_templates' class='list-group-item'>
<span class='label label-info'>templates</span> - Templates</a>
<a href='?choice=none.crud_user_log' class='list-group-item'>
<span class='label label-info'>user_log</span> - User Log</a>
<a href='?choice=none.crud_user_session_activity' class='list-group-item'>
<span class='label label-info'>user_session_activity</span> - User Session Activity</a>
<a href='?choice=none.crud_view_invoices2' class='list-group-item'>
<span class='label label-info'>view_invoices2</span> - View Invoices List</a>
<a href='?choice=none.crud_view_invoices' class='list-group-item'>
<span class='label label-info'>view_invoices</span> - View Invoices List</a>
<a href='?choice=none.crud_vlans' class='list-group-item'>
<span class='label label-info'>vlans</span> - IP VLAN Manager</a>
<a href='?choice=none.crud_vps_bandwidth' class='list-group-item'>
<span class='label label-info'>vps_bandwidth</span> - VPS Bandwidth</a>
<a href='?choice=none.crud_vps_ips' class='list-group-item'>
<span class='label label-info'>vps_ips</span> - VPS IP Adddress Space</a>
<a href='?choice=none.crud_vps' class='list-group-item'>
<span class='label label-info'>vps</span> - Virtual Private Servers</a>
<a href='?choice=none.crud_vps_list_free_ips2' class='list-group-item'>
<span class='label label-info'>vps_list_free_ips2</span> - Free/Available IPs For VPs Servers</a>
<a href='?choice=none.crud_vps_list_free_ips' class='list-group-item'>
<span class='label label-info'>vps_list_free_ips</span> - Free/Available IPs For VPs Servers</a>
<a href='?choice=none.crud_vps_list' class='list-group-item'>
<span class='label label-info'>vps_list</span> - Virtual Private Servers (VPS)</a>
<a href='?choice=none.crud_vps_masters' class='list-group-item'>
<span class='label label-info'>vps_masters</span> - VPS Host Servers</a>
<a href='?choice=none.crud_vps_next_servers' class='list-group-item'>
<span class='label label-info'>vps_next_servers</span> - VPS Next Setup Servers</a>
<a href='?choice=none.crud_webhosting_list' class='list-group-item'>
<span class='label label-info'>webhosting_list</span> - Website List</a>
<a href='?choice=none.crud_whm_listaccts' class='list-group-item'>
<span class='label label-info'>whm_listaccts</span> - Accounts List</a>
<a href='?choice=none.crud_whos_online' class='list-group-item'>
<span class='label label-info'>whos_online</span> - Whos Online</a>
</div>
");
}