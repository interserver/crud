<?php
/**
 * CRUD System
 * @author Joe Huss <detain@interserver.net>
 * @copyright 2020
 * @package MyAdmin
 * @category Admin
 */
use MyCrud\Crud;

/**
 * crud_view_invoices()
 * @return void
 */
function crud_view_invoices()
{
    $crud = Crud::init("select
date_format(invoices_date, '%Y-%m-%d') as invoices_date,
concat(
  '<img src=\"images/myadmin/',
  if (invoices_type = 1,
	'cashflow',
	if (invoices_type = 2,
	  'budget',
	  if (invoices_type = 10,
		'paypal',
		if (invoices_type = 11,
		  'credit-card',
		  if (invoices_type = 12,
			'merchant-account',
			if (invoices_type = 13,
			  'google-plus',
			  if (invoices_type = 14,
				'card-payment',
				if (invoices_type = 15,
				  'price-tag',
				  if (invoices_type = 16,
					'billing',
					if (invoices_type = 17,
					  'bounced-check',
					  'card-payment'
					)
				  )
				)
			  )
			)
		  )
		)
	  )
	)
  ),
  '.png\" border=0 alt=\"',
  invoices_type,
  '\" style=\"width: 24px;\">'
) as invoices_type,
__TITLE_FIELD__ as invoices_service,
if (invoices_type = 1,
  replace(
	invoices_description,
	concat(
	  '(Repeat Invoice: ',
	  invoices_extra,
	  ') '
	),
	''
  ),
  invoices_description
) as invoices_description,
invoices_amount,
if (invoices_type = 1,
 concat(
   '<img src=\"/images/myadmin/',
   if (invoices_paid=1,
	 'checkmark',
	 'delete'
   ),
   '.png\" alt=\"',
   invoices_paid,
   '\" border=0 style=\"width: 24px;\">'
 ),
 ''
) as invoices_paid,
invoices_id from invoices left join __TABLE__ on invoices_service=__PREFIX___id where invoices_module='__MODULE__'")
        ->enable_labels()
        ->set_use_html_filtering(false)
        ->set_labels(['invoices_date' => 'Date', 'invoices_type' => 'Type', 'invoices_service' =>  'Service', 'invoices_description' => 'Description', 'invoices_amount' => 'Cost', 'invoices_paid' => 'Paid', 'invoices_id' => 'ID'])
        ->set_title(_('View Invoices List'));
    function_requirements('has_acl');
    if ($GLOBALS['tf']->ima != 'admin' || !has_acl('system_config')) {
        $crud->disable_edit()
            ->disable_delete();
    }
    $crud->go();
}
