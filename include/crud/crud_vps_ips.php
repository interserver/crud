<?php
function crud_vps_ips() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	$crud = crud::init("select ips_ip, ips_server, vps_name as server, vps, login, custid  from vps_ips left join vps_masters on vps_masters.vps_id=ips_server 
left join (select vps_custid as custid, vps_ip as ip, vps_id as vps, account_lid as login from vps 
left join accounts on account_id=vps_custid where vps_status='active' and vps_ip != '' union 
select repeat_invoices_custid as custid, substring(substring(repeat_invoices_description, 1, 
locate(' for VPS', repeat_invoices_description) - 1), 15) as ip, substring(repeat_invoices_description, 
locate(' for VPS', repeat_invoices_description) + 9) as vps, account_lid as login from repeat_invoices 
left join accounts on account_id=repeat_invoices_custid where repeat_invoices_description like 'Additional IP % for VPS%') 
as usedips on usedips.ip=vps_ips.ips_ip
")
		->set_title("VPS IP Adddress Space")
		->go();
}
