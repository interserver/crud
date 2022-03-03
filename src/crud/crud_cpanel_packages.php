<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

function get_cpanel_packages() {
	$cpl = new \Detain\Cpanel\Cpanel(CPANEL_LICENSING_USERNAME, CPANEL_LICENSING_PASSWORD);
	$response = $cpl->fetchPackages(true);
	$license = [];
	foreach ($response['package'] as $idx => $data) {
		$licenses[] = $data;
	}
	return $licenses;    
}

/**
 * 
 * @return void
 */
function crud_cpanel_packages()
{
	function_requirements('has_acl');
	if ($GLOBALS['tf']->ima != 'admin') {
		dialog(_('Not Admin'), _('Not Admin or you lack the permissions to view this page.'));
		return false;
	}
	page_title(_('CPanel Packages'));
	Crud::init('get_cpanel_packages', 'default', 'function')
		->set_title(_('CPanel Packages'))
		->enable_fluid_container()
		->disable_edit()
		->disable_delete()
		->go();
}

/*
		Internal        External 
Count   VPS     Dedi    VPS     Dedi
5       12.50           17.60
30      17.50           26.40
50      25.00
100     32.00   32.00   39.60   39.60
150     +2.50   +2.50   +2.50   +2.50       +0.05 per account over 100
200     +7.50   +7.50   +7.50   +7.50       +0.10 per account over 100
250     +12.50  +12.50  +12.50  +12.50

*/