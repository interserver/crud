/**
 * processing submit buttons and handles the responses
 *
 * @param what the form your submitting (ie edit, add, delete)
 * @param that the this object that triggered the call
 *
 * @returns {Boolean}
 */

function sortTable(table, order) {
		console.log("sort-table");
    var asc   = order === 'asc',
        tbody = table.find('tbody');

    tbody.find('tr').sort(function(a, b) {
        if (asc) {
            return $('td:eq(4)', a).text().localeCompare($('td:eq(4)', b).text());
        } else {
            return $('td:eq(4)', b).text().localeCompare($('td:eq(4)', a).text());
        }
    }).appendTo(tbody);
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
				$('#'+what+'Modal .btn').attr('disabled', FALSE);
				jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Error Charging the Credit-Card</div>");
			} else if (html == 'ok') {
				$('#'+what+'Modal .btn').attr('disabled', FALSE);
			} else {
				$('#'+what+'Modal .btn').attr('disabled', FALSE);
				jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>"+html+"</div>");
			}
		},
		error: function() {
			$('#'+what+'Modal .btn').attr('disabled', FALSE);
			jQuery('#'+what+'Modal .error_message').html("<div class=\"alert alert-danger alert-dismissable\"><button type=\"button\" class=\"close\" data-dismiss=\"alert\" aria-hidden=\"true\">&times;</button>Error occurred!</div>");
		},
		beforeSend: function()
		{
			$('#'+what+'Modal .btn').attr('disabled', TRUE);
			jQuery('#'+what+'Modal .error_message').html('<div style="margin: 15px; text-align: center;"><i class="fa fa-spinner fa-spin fa-2x"></i> <span style="margin-left: 10px;font-size: 18px;">Processing "+what+"</span><div>');
		}
	});
	return false;
}

/**
 * triggers the edit form to show up for a given row and populates the form with the rowws data
 *
 * @param that the this object from the row that triggered the call
 */
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

/**
 * triggers the delete  form to show up for a given row and populates the form with the rowws data
 *
 * @param that the this object from the row that triggered the call
 */
function crud_delete_form(that) {
	var parent = get_crud_row_id(that);
	var row = crud_rows[parent], field, value;
	console.log(row);
	console.log(row[crud_primary_key]);
	jQuery("#primary_key").val(row[crud_primary_key]);
	jQuery('#editModal .error_message').html();
	jQuery("#deleteModal").modal("show");
}

/**
 * gets the row index on the table / array
 *
 * @param that the this object for the given row
 */
function get_crud_row_idx(that) {
	return replaceAll(jQuery(that).parent().parent().attr("id"), "itemrow", "");
}

/**
 * gets the row primary key id on the table / array
 *
 * @param that the this object for the given row
 */
function get_crud_row_id(that) {
	var parent = get_crud_row_idx(that);
	var row = crud_rows[parent], field, value;
	return row[crud_primary_key];
}

/**
 * replaces all text within a string similar to php's str_replace() function
 *
 * @param string str the original string to find+replace text in
 * @param string find the text to replace
 * @param string replace the replacement text
 * @returns string the string with the text replaced
 */
function replaceAll(str, find, replace) {
	return str.replace(new RegExp(find, 'g'), replace);
}

/**
 * performs a crud search request
 *
 * @param that
 * @param terms
 */
function crud_search(that, terms) {
	crud_search_terms = terms;
	jQuery('.crud-header-buttons a').removeClass('active');
	if (jQuery(that).attr('id') != 'crud_search_button')
		jQuery(that).addClass('active');
	crud_load_page();
}

/**
 * gets the crud URL to use with the current pagination/offset/order information
 *
 * @returns string the URL to use
 */
function get_crud_url() {
	var url = jQuery("#paginationForm").attr("action");
	if (typeof url == undefined || typeof url == "undefined") {
		url = document.location.pathname.replace(/\/[^\/]*$/,'')+'/ajax.php'+document.location.search.replace('choice=none\.', 'choice=crud&crud=')+'&action=list';
		console.log("Got undefined action= contents from #pagionationForm so used fallback method and generated: "+url);
	}
	url = url+"&order_by="+crud_order_by+"&order_dir="+crud_order_dir+"&offset="+crud_page_offset+"&limit="+crud_page_limit;
	if (crud_search_terms.length > 0)
		url = url + "&search=" + JSON.stringify(crud_search_terms);
	return url;
}

/**
 * handles click events and reads the export type from the links parent element attribute ''data-type'
 *
 * @param that
 */
function crud_export(that) {
	event.preventDefault();
	var obj = jQuery(that);
	var parent = obj.parent();
	var format = parent.attr('data-type');
	console.log("Exporting to format "+format);
	var url = get_crud_url() + "&format="+format;
	url = url.replace("action=list","action=export");
	window.location = url;
	//$.ajax({ url: url });

}

/**
 * grabs crud page data JSON data and populates the table
 *
 * @param callback
 */
function crud_load_page(callback) {
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
		console.log("page finished loading "+crud_rows.length+" rows");
		if (typeof callback != "undefined") {
			callback();
		}
	});
sortTable(jQuery('.webhosting-list'),'asc');
}

/**
 * updates the pager links with appropriate page numbers
 */
function crud_update_pager() {
	var x, first, page_links = [], page_html = '';
	crud_page = (crud_page_offset / crud_page_limit) + 1;
	//console.log(crud_page);
	//console.log(crud_page_offset);
	//console.log(crud_page_limit);
	//console.log('crud_total_counts'+crud_total_count);
	crud_total_pages = Math.ceil(crud_total_count / crud_page_limit);
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

/**
 * sets up the edit item click bind event
 */
function crud_setup_edit_binds() {
	jQuery("#editModal").on("shown.bs.modal", function(e) {
		jQuery("#editModal input").focus();
	});
	jQuery("#editModal form").on("submit", function(event) {
		event.preventDefault();
		crud_submit_handler('edit', this);
	});
}

/**
 * sets up the delete item bind events
 */
function crud_setup_delete_binds() {
	jQuery("#deleteModal form").on("submit", function(event) {
		event.preventDefault();
		crud_submit_handler('delete', this);
	});
}

/**
 * sets up the search click bind events
 */
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

/**
 * updates the sort order.  called from a click type event on a sort button. the link/button
 * object is passed to it and it reads the sort information from the element attributes
 *
 * @param that
 */
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

/**
 * setup the pagination click bind events
 */
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

/**
 * sets up the mass click bind events
 */
function crud_setup_mass_binds() {
	jQuery("#crud-table #checkall").click(function () {
		if (jQuery("#crud-table #checkall").is(':checked')) {
			jQuery("#crud-table input[type=checkbox]").each(function () {
				jQuery(this).prop("checked", TRUE);
			});
		} else {
			jQuery("#crud-table input[type=checkbox]").each(function () {
				jQuery(this).prop("checked", FALSE);
			});
		}
	});
}

/**
 * sets up the page limit click bindings
 */
function crud_setup_limit_binds() {
	jQuery('.crud .row-counts button').on('click', function(event) {
		var obj = jQuery(this);
		crud_page_limit = obj.attr('data-limit');
		jQuery('.crud .row-counts button').removeClass('active');
		obj.addClass('active');
		crud_load_page();
	});
}

/**
 * setup the click type bind events
 */
function crud_setup_binds() {
	crud_setup_edit_binds();
	crud_setup_delete_binds();
	crud_setup_search_binds();
	crud_setup_pager_binds();
	crud_setup_limit_binds();
	crud_setup_mass_binds();
}

/**
 * defines the .refreshMe() function for jQuery which will refresh a page and show a spinner while refreshing
 */
$.fn.refreshMe = function(opts){
	var $this = this,
	defaults = {
		panel: '.crud',
		refreshcontainer: '.refresh-container',
		started: function(){},
		completed: function(){}
	},
	settings = $.extend(defaults, opts);

	var panelToRefresh = $this.parents(settings.panel).find(settings.refreshcontainer);
	//var dataToRefresh = $this.parents(settings.panel).find('.refresh-data');
	var started = settings.started;		//function before timeout
	var completed = settings.completed;	//function after timeout

	$this.click(function(event){
		$this.find('.fa').addClass("fa-spin");
		panelToRefresh.show();
		started($this, panelToRefresh);
		/*
		completed(dataToRefresh);
		panelToRefresh.fadeOut(800);
		$this.removeClass("fa-spin");
		*/
		return false;
	})
};

/**
 * handles refreshing the crud contents
 */
function crud_setup_refresh() {
	$('.refresh').refreshMe({
		started: function(refreshobj, panel){
			crud_load_page(function(){
				panel.fadeOut(800);
				refreshobj.find('.fa').removeClass('fa-spin');
			});
		}
	});
}

/**
 * prepaires the page for printing. it does this by:
 *  - it removes the page contents other than the table
 *  - hides any table elements with the 'printer-hidden' class
 *  - replaces all <a href></a> type html in the page with the text inside the link
 *  - delays the printing by 250ms so the page changes can fully take place
 * upon finishing or canceling the print window it restores the original page contents
 */
function crud_print() {
	event.preventDefault();
	jQuery('.printer-hidden').hide();
	var oldPage = document.body.innerHTML;
	jQuery("#crud-table td a").each(function() {
		jQuery(this).parent().text(jQuery(this).parent().text());
	});
	var obj = jQuery('.crud .table-responsive');
	var divElements = obj.html();
	document.body.innerHTML = "<html><head><title></title></head><body>"+divElements+"</body>";
	setTimeout(function(oldpage) {
		window.print();
		document.body.innerHTML = oldPage;
		jQuery('.printer-hidden').show();
	}, 250);
}

/**
 * called when th page loads and performs any calls to setup the crud system
 *
 * @type Object
 */
jQuery(document).ready(function () {
	crud_setup_binds();
	crud_setup_refresh();
	sortTable(jQuery('.webhosting-list'),'asc');
	jQuery("[data-toggle=tooltip]").tooltip();
});
