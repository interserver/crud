<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2019
 * @package MyAdmin
 * @category Admin
 */
use \MyCrud\Crud;

/**
 * crud_dns_manager()
 * @return void
 */
function crud_dns_manager()
{
	if (isset($GLOBALS['tf']->variables->request['new']) && $GLOBALS['tf']->variables->request['new'] == 1 && verify_csrf_referrer(__LINE__, __FILE__)) {
		function_requirements('validIp');
		function_requirements('add_dns_domain');
		if (isset($GLOBALS['tf']->variables->request['ip'])) {
            if (validIp($GLOBALS['tf']->variables->request['ip'])) {
                $ip = trim($GLOBALS['tf']->variables->request['ip']);
                if (isset($GLOBALS['tf']->variables->request['domain']) && trim($GLOBALS['tf']->variables->request['domain']) != '') {
                    $domain = trim($GLOBALS['tf']->variables->request['domain']);
                    $result = add_dns_domain($domain, $ip);
                    myadmin_log('dns', 'debug', "add_dns_domain($domain, $ip) = ".json_encode($result), __LINE__, __FILE__);
                    add_output($result['status_text']);
                }
                if (isset($GLOBALS['tf']->variables->request['domains']) && !in_array(trim($GLOBALS['tf']->variables->request['domains']), ['', 'Domain Names...'])) {
                    $domains = explode("\n", $GLOBALS['tf']->variables->request['domains']);
                    foreach ($domains as $domain) {
                        $domain = trim($domain);
                        if ($domain != '') {
                            $result = add_dns_domain($domain, $ip);
                            add_output('<div class="container alert alert-danger">'.$result['status_text'].'</div>');
                        }
                    }
                }
            } else {
                add_output('<div class="container alert alert-danger">Invalid IP '.$GLOBALS['tf']->variables->request['ip'].'</div>');
            }
            
        }
	}
	Crud::init("select domains.id, domains.name, records.content from domains left join records on domains.id=records.domain_id and records.type='A' and (records.name=domains.name or records.name='')", 'pdns', 'sql', '/^account$/m')
		->set_title(_('DNS Manager'))
		->disable_delete()
		->disable_edit()
		->enable_labels()
		->set_labels(['id' => _('ID'), 'name' => _('Domain Name'), 'content' => _('IP Address')])
		->set_header('
<form>
	<input type="hidden" name="choice" value="none.crud_dns_manager">
	<input type="hidden" name="new" value="1">
	<div class="row">
		<div class="col-md-2 col-md-offset-2">
			<div class="printer-hidden" style="vertical-align: middle;">
				<label style="margin-top: 5px;">'._('Add Domain to DNS').'</label>
			</div>
		</div>
		<div class="col-md-3">
			<div class="printer-hidden">
				<div class="input-group">
					<div class="input-group-btn">
						<button type="button" class="btn btn-default" aria-label="'._('Domain Name').'" style="padding: 0px;"><img src="/images/myadmin/domain.png" border="0" style="width: 32px;"></button>
					</div>
					<input class="form-control" aria-label="'._('Domain Name').'" placeholder="'._('Domain like').' mycoolsite.com" name="domain">
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="printer-hidden">
				<div class="input-group">
					<div class="input-group-btn">
						<button type="button" class="btn btn-default" aria-label="'._('IP Address').'" style="padding: 0px;"><img src="/images/myadmin/web-address.png" border="0" style="width: 32px;"></button>
					</div>
					<input class="form-control" aria-label="'._('IP Address').'" placeholder="'._('IP Address like').' 0.0.0.0" name="ip">
				</div>
			</div>
		</div>
		<div class="col-md-2">
			<div class="printer-hidden">
				<input class="form-control btn btn-default" type="submit" value="'._('Add DNS Entry').'">
			</div>
		</div>
	</div>
</form>
')
		->add_row_button('none.basic_dns_editor&edit=%id%', _('Edit DNS Records for this Domain'), 'primary', 'cog')
		->add_row_button('none.dns_delete&id=%id%', _('Delete this Domain and its Records from DNS'), 'danger', 'trash')
		->go();
}
