function crud_get_order_row(order) {
	var html;
	html = '\
		<tr data-module="'+order.module+'" data-id="'+order.order_id+'" data-status="'+order.order_status+'">\
			<td>\
						'+order.tblname+' Order ID <a href="index.php?choice=none.view_website2&id='+order.order_id+'" title="View Website Order" target="_blank"><span class="badge">'+order.order_id+'</span></a><br>\
						<a href="http://'+order.title_field+'" title="Goto Website" target="_blank">'+order.title_field+'</a><br>\
						Status\
						';
	if (order.order_status == 'active')
		html = html + '<span class="label label-success">'+order.order_status+'</span><br>';
	else if (order.order_status == 'pending')
		html = html + '<span class="label label-info">'+order.order_status+'</span><br>';
	else if (order.order_status == 'pendapproval')
		html = html + '<span class="label label-primary">'+order.order_status+'</span><br>';
	else if (order.order_status == 'expired')
		html = html + '<span class="label label-warning">'+order.order_status+'</span><br>';
	else if (order.order_status == 'canceled')
		html = html + '<span class="label label-danger">'+order.order_status+'</span><br>';
	else
		html = html + '<span class="label label-default">'+order.order_status+'</span><br>';
	html = html + '\
						'+order.services_name+'<br>\
						';
	if (order.repeat_invoices_frequency == '')
		html = html + 'Term Unknown<br>';
	else if (order.repeat_invoices_frequency == 1)
		html = html + 'Term '+order.repeat_invoices_frequency+' Month<br>';
	else
		html = html + 'Term '+order.repeat_invoices_frequency+' Months<br>';
	html = html + 'Ordered: ' + order.order_date;
	html = html + '\
			</td>\
			<td>\
						<a href="index.php?choice=none.edit_customer3&customer='+order.account_id+'" title="Edit Customer" target="_blank"><i class="fa fa-envelope"></i> '+order.account_lid+'</a><br>\
						<address>';
	if (order.name != '' && typeof order.name != 'object')
		html = html + '\
							<i class="fa fa-user"></i> '+order.name+'<br>';
	if (order.address != '' && typeof order.address != 'object')
		html = html + '\
							<i class="fa fa-road"></i> '+wordwrap(order.address, 30, '<br/>\n<i class="fa fa-empty" ></i> ', false)+'<br>';
	if (order.address2 != '' && order.address2 != '-' && typeof order.address2 != 'object')
		html = html + '\
							<i class="fa fa-empty" ></i> '+order.address2+'<br>';
	if (order.city != '' && typeof order.city != 'object')
		html = html + '\
							<i class="fa fa-empty"></i> '+order.city+', '+order.state+' '+order.zip+'<br>';
	if (order.country != '' && typeof order.country != 'object') {
		var country=String(order.country);
		html = html + '\
							<i class="fa fa-empty"></i> '+order.country+' <img class="flag" src="/images/flags/'+country.toLowerCase()+'.png" alt="'+order.country+'"><br>';
	}
	html = html + '\
						</address>\
			</td>\
			<td>\
						Payment Method '+order.payment_method+'<br>\
						';
	if (order.invoices_type == 10)
		html = html + '<a onclick="modal_transaction(\''+order.invoices_description+'\'); return false;" title="View PayPal Transaction"><i class="fa fa-paypal"></i> '+order.invoices_description+'</a><br>';
	else if (order.invoices_type == 11)
		html = html + '<i class="fa fa-credit-card"></i> '+order.cc+'</a><br>';
	else if (order.cc != '' && typeof order.cc != 'object')
		html = html + '<i class="fa fa-credit-card"></i> '+order.cc+'</a><br>';
	if (order.maxmind_riskscore == '' || typeof order.maxmind_riskscore == 'object')
		html = html + '\
						Fraud Risk Unknown <a href="index.php?choice=none.view_maxmind&customer='+order.account_id+'" title="Refresh MaxMind Score" target="_blank"><i class="fa fa-refresh"></i></a><br>';
	else
		html = html + '\
						Fraud Risk '+order.maxmind_riskscore+'% <a onclick="modal_maxmind('+order.account_id+'); return false;" title="View MaxMind Report"><i class="fa fa-search"></i></a><br>';
	if (typeof order.invoices_amount != "undefined" && order.invoices_amount != null && order.invoices_amount != "")
		html = html + 'Paid $'+order.invoices_amount+'<br>';
	if (typeof order.cc_response != "undefined" && order.cc_response != null && order.cc_response != "")
		html = html + 'CC Response:'+order.cc_response+'<br>';
	if (typeof order.coupon != "undefined" && order.coupon != null && order.coupon != "")
		html = html + 'Coupon:'+order.coupon+'<br>';
	html = html + '\
			</td>\
			<td>';
	if ((order.order_status == 'pending' || order.order_status == 'pendapproval') && order.cc != '' && typeof order.cc != 'object')
		html = html + '\
						<a onclick="approval_handler(\'activate\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Enable Credit-Card Use on this account as needed and then proceeding to attempt charging the card to activate the order.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-credit-card fa-stack-1x"></i>\
								<i class="fa fa-check fa-stack-1x text-success"></i>\
							</span>\
							Enable Credit-Card + Activate\
						</a><br>';
	else if (order.payment_method == 'cc')
		html = html + '\
						<a onclick="approval_handler(\'cancel\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Cancel this order and disable CC use for this customer.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-credit-card fa-stack-1x"></i>\
								<i class="fa fa-close fa-stack-1x text-danger"></i>\
							</span>\
							Disable Credit-Card + Cancel\
						</a><br>';
	else if (order.invoices_type != '' && typeof order.invoices_type != 'object')
		html = html + '\
						<a onclick="approval_handler(\'cancel\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Cancel this order.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-close fa-stack-1x text-danger"></i>\
							</span>\
							Cancel\
						</a><br>';
	if (order.account_status != 'active')
		html = html + '\
						<a onclick="approval_handler(\'enable_account\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Enable this clients account.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-user fa-stack-1x"></i>\
								<i class="fa fa-check fa-stack-1x text-success"></i>\
							</span>\
							Enable Account\
						</a><br>';
	else
		html = html + '\
						<a onclick="approval_handler(\'disable_account\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Disable this clients account.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-user fa-stack-1x"></i>\
								<i class="fa fa-close fa-stack-1x text-danger"></i>\
							</span>\
							Disable Account\
						</a><br>';
	if (order.invoices_type != '' && typeof order.invoices_type != 'object') {
		if (order.payment_method == 'cc')
		html = html + '\
						<a onclick="approval_handler(\'refund_cc\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Refund this clients creditcard charge for this order.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-credit-card fa-stack-1x"></i>\
								<i class="fa fa-undo fa-stack-1x text-danger"></i>\
							</span>\
							Refund Credit Card\
						</a><br>';
		else
		html = html + '\
						<a onclick="approval_handler(\'refund_paypal\', '+order.order_id+', \''+order.module+'\'); return false;" class="btn btn-default" title="Refund this PayPal transaction.">\
							<span class="fa-stack fa-lg">\
								<i class="fa fa-paypal fa-stack-1x"></i>\
								<i class="fa fa-undo fa-stack-1x text-danger"></i>\
							</span>\
							Refund PayPal\
						</a><br>';
	}
	html = html + '\
			</td>\
		</tr>';
	return html;
}

function crud_submit_handler(what, that) {
	var disabled = jQuery("#"+what+"ModalForm input[disabled], #"+what+"ModalForm select[disabled]");
	disabled.removeAttr("disabled");
	var url = jQuery("#"+what+"ModalForm").attr("action");
	var data = jQuery(that).serialize();
	console.log("calling "+url+" with post data "+data);
	disabled.attr("disabled", "disabled");
	$.ajax({
		type: 'POST',
		url: url,
		data: data,
		success: function(html){
			//console.log("handler returned html: "+html);
			jQuery('#'+what+'Modal .error_message').html('');
			if(html.substring(0, 4)=='true') {
				jQuery('#'+what+'Modal .error_message').html('<div style="margin: 15px; text-align: center;"><i class="fa fa-spinner fa-spin fa-2x"></i> <span style="margin-left: 10px;font-size: 18px;">Redirecting</span><div>');
				if (html.length == 4) {
					window.location="index.php";
				} else {
					window.location=html.substring(4);
				}
			} else if (html == 'error') {
				$('#'+what+'Modal .btn').attr('disabled', false);
				jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Error Charging the Credit-Card</div>");
			} else if (html == 'ok') {
				$('#'+what+'Modal .btn').attr('disabled', false);
			} else {
				$('#'+what+'Modal .btn').attr('disabled', false);
				jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>"+html+"</div>");
			}
		},
		error : function() {
			$('#'+what+'Modal .btn').attr('disabled', false);
			jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Error occurred!</div>");
		},
		beforeSend:function()
		{
			$('#'+what+'Modal .btn').attr('disabled', true);
			jQuery('#'+what+'Modal .error_message').html('<div style="margin: 15px; text-align: center;"><i class="fa fa-spinner fa-spin fa-2x"></i> <span style="margin-left: 10px;font-size: 18px;">Processing "+what+"</span><div>');
		}
	});
	return false;
}

function crud_edit_form(that) {
	var parent = get_crud_row_idx(that);
	//console.log(get_crud_row_id(that));
	var row = crud_rows[parent], field, value;
	console.log(row);
	for (field in row) {
		value = row[field];
		jQuery("#"+field).val(value);
	}
	jQuery('#editModal .error_message').html();
	jQuery("#editModal").modal("show");
}

function crud_delete_form(that) {
	var parent = get_crud_row_id(that);
	var row = crud_rows[parent], field, value;
	console.log(row);
	console.log(row[crud_primary_key]);
	jQuery("#primary_key").val(row[crud_primary_key]);
	jQuery('#editModal .error_message').html();
	jQuery("#deleteModal").modal("show");
}

function crud_approval_list(status, offset, limit) {
	if (typeof jQuery('#order_status_grp .btn.active').attr('data-target') != "undefined" && typeof status == "undefined")
		status = jQuery('#order_status_grp .btn.active').attr('data-target');
	var url = "ajax_pending_approval.php?action=list&status="+status;
	if (typeof offset != "undefined")
		url = url+"&offset="+offset;
	url = url + "&limit=" + document.getElementById('pending_approval_limit').value;
	url = url + "&module=" + document.getElementById('pending_approval_module').value;
	$.getJSON(url, { }, function(json) {
		jQuery('table.orders tbody').html('');
		//console.log(json);
		for (var x = 0; x < json.orders.length; x++)
			jQuery('table.orders tbody').append(crud_get_order_row(json.orders[x]));
		$('.section a[title]').tooltip();
	});
	return false;
}

function get_crud_row_idx(that) {
	return replaceAll(jQuery(that).parent().parent().attr("id"), "itemrow", "");
}

function get_crud_row_id(that) {
	var parent = get_crud_row_idx(that);
	var row = crud_rows[parent], field, value;
	return row[crud_primary_key];
}

function replaceAll(str, find, replace) {
	return str.replace(new RegExp(find, 'g'), replace);
}

function crud_search(that, terms) {
	crud_search_terms = terms;
	jQuery('.crud-header-buttons a').removeClass('active');
	if (jQuery(that).attr('id') != 'crud_search_button')
		jQuery(that).addClass('active');
	crud_load_page();
}

function get_crud_url() {
	var url = jQuery("#paginationForm").attr("action")+"&order_by="+crud_order_by+"&order_dir="+crud_order_dir+"&offset="+crud_page_offset+"&limit="+crud_page_limit;
	if (crud_search_terms.length > 0)
		url = url + "&search=" + JSON.stringify(crud_search_terms)
	return url;
}

function crud_load_page() {
	$.getJSON(get_crud_url(), { }, function(json) {
		crud_rows = json;
		var empty = document.getElementById('itemrowempty').innerHTML;
		var x, row;
		jQuery('#crud-table tbody').html('');
		jQuery('#crud-table tbody').append('<tr id="itemrowempty" style="display: none;">' + empty + '</tr>');
		for(var x = 0; x < json.length; x++) {
			//row = replaceAll(empty, 'display: none;','');
			row = empty;
			for (var field in json[x]) {
				row = replaceAll(row, '%'+field+'%', json[x][field]);
			}
			jQuery('#crud-table tbody').append('<tr id="itemrow'+x+'">' + row + '</tr>');
		}
		crud_update_pager();
		//console.log(json);
		jQuery("[data-toggle=tooltip]").tooltip();
	});
}

function crud_update_pager() {
	var x, first, page_links = [], page_html = '';
	crud_page = (crud_page_offset / crud_page_limit) + 1;
	//console.log("Offset "+crud_page_offset+" Limit "+crud_page_limit+" Page "+crud_page);
	if (crud_page > 1)
		jQuery('#crud-pager-prev').removeClass('disabled');
	else
		jQuery('#crud-pager-prev').addClass('disabled');
	if (crud_page < crud_total_pages)
		jQuery('#crud-pager-next').removeClass('disabled');
	else
		jQuery('#crud-pager-next').addClass('disabled');
	page_links[0] = 1;
	first = crud_page - 2;
	if (first < 2)
		first = 2;
	for (x = 0; x < 4; x++)
		if (page_links.indexOf(first + x) == -1 && first + x < crud_total_pages)
			page_links[page_links.length] = first + x;
	page_links[page_links.length] = crud_total_pages;
	var page_html = '', page_offset;
	for (x = 0; x < page_links.length; x++) {
		page_html = page_html + '<li class="crud-page';
		page_offset = ((page_links[x] - 1) * crud_page_limit);
		if (crud_page_offset == page_offset)
			page_html = page_html + ' active';
		page_html = page_html + '"><a href="" class="" data-offset="'+page_offset+'">'+page_links[x]+'</a></li>';
	}
	jQuery('.crud .pagination li.crud-page').remove();
	jQuery('.crud .pagination #crud-pager-prev').after(page_html);
	//jQuery('.crud .pagination li.crud-page').removeClass('active');
	//jQuery('.crud .pagination li.crud-page a[data-offset="'+crud_page_offset+'"]').parent().addClass('active');
	crud_setup_pager_binds();
}

function crud_setup_edit_binds() {
	jQuery("#editModal").on("shown.bs.modal", function(e) {
		jQuery("#editModal input").focus();
	});
	jQuery("#editModal form").on("submit", function(event) {
		event.preventDefault();
		crud_submit_handler('edit', this);
	});
}

function crud_setup_delete_binds() {
	jQuery("#deleteModal form").on("submit", function(event) {
		event.preventDefault();
		crud_submit_handler('delete', this);
	});
}

function crud_setup_search_binds() {
	jQuery('#crud-search').on('click', function(event) {
		event.preventDefault();
		jQuery('#crud-search').hide();
		jQuery('#crud-search-more').show();
	});
	jQuery('#crud_search_button').on('click', function(event) {
		event.preventDefault();
		crud_search(this, [jQuery('#crud_search_column').val(),'=',jQuery('.crud-searchdata.crud-search-active').val()]);
	});
}

function crud_update_sort(that) {
	event.preventDefault();
	var obj = jQuery(that);
	var parent = obj.parent();
	crud_order_dir = parent.attr('data-order-dir');
	crud_order_by = parent.attr('data-order-by');
		//console.log("got a click on "+crud_order_by+" dir "+crud_order_dir);
	if (crud_order_dir == 'asc')
		parent.attr('data-order-dir', 'desc');
	else
		parent.attr('data-order-dir', 'asc');
	//jQuery('.crud #itemrowheader th').removeClass('active');
	jQuery('.crud #itemrowheader .header_link i').css('opacity', '0.3').removeClass('fa-sort-desc').removeClass('fa-sort-asc').addClass('fa-sort');
	//jQuery(this).parent().addClass('active');
	//console.log("current classes "+obj.attr('class')+" setting to "+crud_order_dir);
	obj.find('i').css('opacity', '1').removeClass('fa-sort').removeClass('fa-sort-desc').removeClass('fa-sort-asc').addClass('fa-sort-'+crud_order_dir);
	crud_load_page();
}

function crud_setup_pager_binds() {
	jQuery('.crud .pagination .crud-page a').on('click', function(event) {
		event.preventDefault();
		crud_page_offset = jQuery(this).attr('data-offset');
		jQuery('.crud .pagination li ').removeClass('active');
		jQuery(this).parent().addClass('active');
		crud_load_page();
	});
	jQuery('#crud-pager-prev a').on('click', function(event) {
		event.preventDefault();
		crud_page_offset = crud_page_offset - crud_page_limit;
		if (crud_page_offset < 0)
			crud_page_offset = 0;
		crud_load_page();
	});
	jQuery('#crud-pager-next a').on('click', function(event) {
		event.preventDefault();
		crud_page_offset = crud_page_offset + crud_page_limit;
		if ((crud_page_offset / crud_page_limit) + 1 >  crud_total_pages)
			crud_page_offset = (crud_total_pages - 1 ) * crud_page_limit;
		crud_load_page();
	});
}

function crud_setup_mass_binds() {
	jQuery("#crud-table #checkall").click(function () {
		if (jQuery("#crud-table #checkall").is(':checked')) {
			jQuery("#crud-table input[type=checkbox]").each(function () {
				jQuery(this).prop("checked", true);
			});
		} else {
			jQuery("#crud-table input[type=checkbox]").each(function () {
				jQuery(this).prop("checked", false);
			});
		}
	});
}

function crud_setup_limit_binds() {
	jQuery('.crud .row-counts button').on('click', function(event) {
		var obj = jQuery(this);
		crud_page_limit = obj.attr('data-limit');
		jQuery('.crud .row-counts button').removeClass('active');
		obj.addClass('active');
		crud_load_page();
	});
}

function crud_setup_binds() {
	crud_setup_edit_binds();
	crud_setup_delete_binds();
	crud_setup_search_binds();
	crud_setup_pager_binds();
	crud_setup_limit_binds();
	crud_setup_mass_binds();
}

jQuery(document).ready(function () {
	crud_setup_binds();
	jQuery("[data-toggle=tooltip]").tooltip();
});

