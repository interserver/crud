function get_order_row(order) {
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

function approval_list(status, offset, limit) {
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
			jQuery('table.orders tbody').append(get_order_row(json.orders[x]));
		$('.section a[title]').tooltip();
	});
	return false;
}

function submit_handler(what, that) {
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
function edit_form(that) {
	var parent = jQuery(that).parent().parent().attr("id").replace("itemrow", "");
	var row = crud_rows[parent], field, value;
	console.log(row);
	for (field in row) {
		value = row[field];
		jQuery("#"+field).val(value);
	}
	jQuery("#editModal").modal("show");
}

function delete_form(that) {
	var parent = jQuery(that).parent().parent().attr("id").replace("itemrow", "");
	var row = crud_rows[parent], field, value;
	console.log(row);
	console.log(row[primary_key]);
	jQuery("#primary_key").val(row[primary_key]);
	jQuery("#deleteModal").modal("show");
}

jQuery(document).ready(function () {
	jQuery("#editModal").on("shown.bs.modal", function(e) {
		jQuery("#editModal input").focus();
	});
	jQuery("#editModal form").on("submit", function(event) {
		event.preventDefault();
		submit_handler('edit', this);
	});
	jQuery("#deleteModal form").on("submit", function(event) {
		event.preventDefault();
		submit_handler('delete', this);
	});
});

