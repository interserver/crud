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
 * returns an array of all the modules and tables
 *
 */
function get_crud_tables() {
	$return = [
		'modules' => [],
		'tables' => []
	];
	foreach (['domains', 'helpdesk', 'innertell', 'pdns'] as $module)
		if (isset($GLOBALS[$module.'_dbh'])) {
			$dbh = $GLOBALS[$module.'_dbh'];
			$dbName = $dbh->database;
			$return['modules'][$module] = $dbh;
			$return['tables'][$module] = [];
			$dbh->query("show full tables where Table_Type='BASE TABLE'", __LINE__, __FILE__);
			while ($dbh->next_record(MYSQL_ASSOC)) {
				$table = $dbh->Record['Tables_in_'.$dbName];
				$type = $dbh->Record['Table_type'];
				$return['tables'][$module][] = $table;
			}
		}
	return $return;
}

/**
 * returns an array of the various crud pages sorted into bootstrap levels indicating thier working level
 *
 * @return array an array of the various crud pages sorted into bootstrap levels indicating thier working level
 */
function get_crud_funcs() {
	$functions = [
		'success' => [
			'admins' => ['function' => 'admins', 'title' => 'Administrator Role Assignments'],
			'backups_list' => ['function' => 'crud_backups_list', 'title' => 'Backup List'],
			'domains_list' => ['function' => 'crud_domains_list', 'title' => 'Domains'],
			'quickservers_list' => ['function' => 'crud_quickservers_list', 'title' => 'QuickServers'],
			'same_domain_accounts' => ['function' => 'same_domain_accounts&id=358805', 'title' => 'Accounts matching the @domain.com of client'],
			'ssl_list' => ['function' => 'crud_ssl_list', 'title' => 'SSL Certificates'],
			'vps_list' => ['function' => 'vps_list', 'title' => 'VPS List'],
			'webhosting_list' => ['function' => 'crud_webhosting_list', 'title' => 'Website List'],
			'dns_manager' => ['function' => 'crud_dns_manager', 'title' => 'DNS Manager'],
		],
		'info' => [
			'coupons' => ['function' => 'crud_coupons', 'title' => 'Coupons'],
			'customers' => ['function' => 'crud_customers', 'title' => 'Customers'],
			'licenses_list' => ['function' => 'crud_licenses_list', 'title' => 'License List'],
			'last_logins' => ['function' => 'crud_last_logins', 'title' => 'Last Logn Locations'],
			'month_payment_totals' => ['function' => 'crud_month_payment_totals', 'title' => 'Payments This Month'],
			'dns_editor' => ['function' => 'crud_dns_editor&id=68', 'title' => 'DNS Editor'],
			'view_invoices' => ['function' => 'crud_view_invoices', 'title' => 'View Invoices List'],
		],
		'warning' => [
			'abuse' => ['function' => 'crud_abuse', 'title' => 'Abuse'],
			'active_packages' => ['function' => 'crud_active_packages', 'title' => 'Active Packages'],
			'admin_tickets' => ['function' => 'crud_admin_tickets', 'title' => 'Admin Tickets'],
			'admin_tickets_widget' => ['function' => 'crud_admin_tickets_widget', 'title' => 'Admin Tickets'],
			'backups' => ['function' => 'crud_backups', 'title' => 'Backups'],
			'dedicated_list' => ['function' => 'crud_dedicated_list', 'title' => 'Dedicated List'],
			'domains' => ['function' => 'crud_domains', 'title' => 'Domains'],
			'fantastico_list' => ['function' => 'crud_fantastico_list', 'title' => 'Fantastico License List'],
			'forum_rss' => ['function' => 'crud_forum_rss', 'title' => 'Latest Forum Posts'],
			'form_manager' => ['function' => 'crud_form_manager', 'title' => 'Form Manager'],
			'history_log' => ['function' => 'crud_history_log', 'title' => 'History Log'],
			'innertell_orders' => ['function' => 'crud_innertell_orders', 'title' => 'Dedicated Server Orders'],
			'innertell_search' => ['function' => 'crud_innertell_search', 'title' => 'Search Results'],
			'invoices' => ['function' => 'crud_invoices', 'title' => 'Invoices'],
			'licenses' => ['function' => 'crud_licenses', 'title' => 'Licenses'],
			'monitoring_list' => ['function' => 'crud_monitoring_list', 'title' => 'Monitored Systems'],
			'packages' => ['function' => 'crud_packages', 'title' => 'Your Packages'],
			'paypal_history' => ['function' => 'crud_paypal_history', 'title' => 'Paypal History'],
			'paypal_transactions' => ['function' => 'crud_paypal_transactions', 'title' => 'Paypal Transactions'],
			'queue_log' => ['function' => 'crud_queue_log', 'title' => 'Queue Log'],
			'renewals' => ['function' => 'crud_renewals', 'title' => 'Renewals'],
			'repeat_invoices' => ['function' => 'crud_repeat_invoices', 'title' => 'Repeat Invoices'],
			'reusable_fantastico' => ['function' => 'crud_reusable_fantastico', 'title' => 'Reusable Fantastico'],
			'server_billing_stats' => ['function' => 'crud_server_billing_stats', 'title' => 'Server Billing Stats'],
			'server_actions' => ['function' => 'crud_server_actions', 'title' => 'Recent Server Commands'],
			'session_log' => ['function' => 'crud_session_log', 'title' => 'session log'],
			'ssl' => ['function' => 'crud_ssl', 'title' => 'SSL Certificates'],
			'templates' => ['function' => 'crud_templates', 'title' => 'Templates'],
			'user_log' => ['function' => 'crud_user_log', 'title' => 'User Log'],
			'vlans' => ['function' => 'crud_vlans', 'title' => 'IP VLAN Manager'],
			'vps' => ['function' => 'crud_vps', 'title' => 'Virtual Private Servers'],
			'vps_list_free_ips2' => ['function' => 'crud_vps_list_free_ips2', 'title' => 'Free/Available IPs For VPs Servers'],
			'vps_list_free_ips' => ['function' => 'crud_vps_list_free_ips', 'title' => 'Free/Available IPs For VPs Servers'],
			'vps_masters' => ['function' => 'crud_vps_masters', 'title' => 'VPS Host Servers'],
			'vps_next_servers' => ['function' => 'crud_vps_next_servers', 'title' => 'VPS Next Setup Servers'],
			'whos_online' => ['function' => 'crud_whos_online', 'title' => 'Whos Online'],
			'whm_listaccts' => ['function' => 'crud_whm_listaccts', 'title' => 'Accounts List']
		],
		'danger' => [
			'innertell_pending_orders' => ['function' => 'crud_innertell_pending_orders', 'title' => 'Pending Dedicated Server Orders'],
			'innertell_pending_orders_new' => ['function' => 'crud_innertell_pending_orders_new', 'title' => 'Pending Server Orders'],
			'new_vps' => ['function' => 'crud_new_vps', 'title' => 'Newest VPS Signups'],
			'pending_vps_list' => ['function' => 'crud_pending_vps_list', 'title' => 'Pending Virtual Private Servers (VPS)'],
			'user_session_activity' => ['function' => 'crud_user_session_activity', 'title' => 'User Session Activity'],
			'vps_bandwidth' => ['function' => 'crud_vps_bandwidth', 'title' => 'VPS Bandwidth'],
			'vps_ips' => ['function' => 'crud_vps_ips', 'title' => 'VPS IP Address Space']
		]
	];
	return $functions;
}

/**
 * displays a list of the various CRUD pages
 *
 * @return bool
 */
function cruds() {
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin' || !has_acl('admins_control')) {
		dialog('Not admin', 'Not Admin or you lack the permissions to view this page.');
		return FALSE;
	}
	add_js('bootstrap');
	page_title('CRUDs List');
	$functions = get_crud_funcs();
	$sizes = [];
	foreach ($functions as $level => $functionsArray)
		$sizes[$level] = count($functionsArray);
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
		.cruds.list-group .list-group-item.active span {
			margin-left: 10px;
		}
		.cruds .panel-heading {
			padding: 1px 0px;
		}
		.cruds .panel-body {
			padding: 0px;
		}
	</style>");
	add_output("
	<div class='panel-group' id='cruds-accordion' role='tablist' aria-multiselectable='true'>
		<div class='panel panel-primary cruds list-group'>
			<div class='panel-heading' role='tab' id='headingOne'>
				<a class='panel-title list-group-item active' role='button' data-toggle='collapse' data-parent='#cruds-accordion' href='#collapseOne' aria-expanded='true' aria-controls='collapseOne'>
					CRUD Page Links
					<span class='pull-right label label-danger'>Not Working Yet ({$sizes['danger']})</span>
					<span class='pull-right label label-warning'>Loads ({$sizes['warning']})</span>
					<span class='pull-right label label-info'>Almost Done ({$sizes['info']})</span>
					<span class='pull-right label label-success'>Ready ({$sizes['success']})</span>
					<span class='pull-right'>Key:</span>
				</a>
			</div>
		<div id='collapseOne' class='panel-collapse collapse in' role='tabpanel' aria-labelledby='headingOne'>
			<div class='panel-body'>");
	foreach ($functions as $level => $functionsArray) {
		foreach ($functionsArray as $origFunction => $functionData) {
			add_output("
				<a href='?choice=none.{$functionData['function']}' class='list-group-item' target='_blank'>
					<span class='label label-{$level}'>{$origFunction}</span> {$functionData['title']}
				</a>");
		}
	}
	add_output('
			</div>
		</div>
	</div>'
	);
	$all_tables = get_crud_tables();
	$levels = ['primary', 'info', 'success', 'warning', 'danger'];
	$idx  = 0;
	$key = [];
	$rows = [];
	foreach ($all_tables['tables'] as $module => $tables) {
		$dbh = $all_tables['modules'][$module];
		$dbName = $dbh->database;
		$level = $levels[$idx];
		$size = count($tables);
		$key[] = "<span class='pull-right label label-{$level}'>{$dbName} ({$size})</span>";
		foreach ($tables as $table) {
			$rows[] = "
			<a href='?choice=none.crud_table&db={$module}&table={$table}' class='list-group-item' target='_blank'>
				<span class='label label-{$level}'>{$dbName}</span> {$table}
			</a>";
		}
		$idx++;
		if ($idx == count($levels))
			$idx = 0;
	}
	$key = array_reverse($key);
	add_output("
	<div class='panel panel-primary cruds list-group'>
		<div class='panel-heading' role='tab' id='headingTwo'>
				<a class='panel-title list-group-item active' role='button' data-toggle='collapse' data-parent='#cruds-accordion' href='#collapseTwo' aria-expanded='true' aria-controls='collapseTwo'>
					CRUD Database Table Links
					" . implode("\n					", $key) . "
					<span class='pull-right'>Key:</span>
				</a>
			</div>
			<div id='collapseTwo' class='panel-collapse collapse in' role='tabpanel' aria-labelledby='headingTwo'>
				<div class='panel-body'>
					" . implode("\n				", $rows).'
				</div>
			</div>
		</div>
	</div>'
	);
}
