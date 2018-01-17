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
 * crud_dns_manager()
 * @return void
 */
function crud_dns_manager() {
	if (isset($GLOBALS['tf']->variables->request['new']) && $GLOBALS['tf']->variables->request['new'] == 1 && verify_csrf_referrer(__LINE__, __FILE__)) {
		function_requirements('validIp');
		if (isset($GLOBALS['tf']->variables->request['ip']) && validIp($GLOBALS['tf']->variables->request['ip'])) {
			$ip = trim($GLOBALS['tf']->variables->request['ip']);
			if (isset($GLOBALS['tf']->variables->request['domain']) && trim($GLOBALS['tf']->variables->request['domain']) != '') {
				$domain = trim($GLOBALS['tf']->variables->request['domain']);
				$result = add_dns_domain($domain, $ip);
				add_output($result['status_text']);
			}
			if (isset($GLOBALS['tf']->variables->request['domains']) && !in_array(trim($GLOBALS['tf']->variables->request['domains']), ['', 'Domain Names...'])) {
				$domains = explode("\n", $GLOBALS['tf']->variables->request['domains']);
				foreach ($domains as $domain) {
					$domain = trim($domain);
					if ($domain != '') {
						$result = add_dns_domain($domain, $ip);
						add_output($result['status_text']);
					}
				}
			}
		}
	}
	Crud::init("select domains.id, domains.name, records.content from domains left join records on domains.id=records.domain_id and records.type='A' and (records.name=domains.name or records.name='')", 'pdns', 'sql', '/^account$/m')
		->set_title('DNS Manager')
		->disable_delete()
		->disable_edit()
		->enable_labels()
		->set_labels(['id' => 'ID', 'name' => 'Domain Name', 'content' => 'IP Address'])
		->set_header('
<form>
	<input type="hidden" name="choice" value="none.crud_dns_manager">
	<input type="hidden" name="new" value="1">
	<div class="row">
		<div class="col-md-2 col-md-offset-2">
			<div class="printer-hidden" style="vertical-align: middle;">
				<label style="margin-top: 5px;">Add Domain to DNS</label>
			</div>
		</div>
		<div class="col-md-3">
			<div class="printer-hidden">
				<div class="input-group">
					<div class="input-group-btn">
						<button type="button" class="btn btn-default" aria-label="Domain Name" style="padding: 0px;"><img src="/images/myadmin/domain.png" border="0" style="width: 32px;"></button>
					</div>
					<input class="form-control" aria-label="Domain Name" placeholder="Domain like mycoolsite.com" name="domain">
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="printer-hidden">
				<div class="input-group">
					<div class="input-group-btn">
						<button type="button" class="btn btn-default" aria-label="IP Address" style="padding: 0px;"><img src="/images/myadmin/web-address.png" border="0" style="width: 32px;"></button>
					</div>
					<input class="form-control" aria-label="IP Address" placeholder="IP Address like 0.0.0.0" name="ip">
				</div>
			</div>
		</div>
		<div class="col-md-2">
			<div class="printer-hidden">
				<input class="form-control btn btn-default" type="submit" value="Add DNS Entry">
			</div>
		</div>
	</div>
</form>
')
		->add_row_button('none.basic_dns_editor&edit=%id%', 'Edit DNS Records for this Domain', 'primary', 'cog')
		->add_row_button('none.dns_delete&id=%id%', 'Delete this Domain and its Records from DNS', 'danger', 'trash')
		->go();
}
