{if $select_multiple == true}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
{if isset($row_buttons)}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
<link rel="stylesheet" type="text/css" href="css/breadcrum.css">
<div class="container-fluid">
	<div class="row" style="margin-top: -10px;">
		<div class="col-md-12">
			<div class="breadcrumb">
				<a href="/">Home</a>
				<a class="active text-capitalize">{$module}</a>
			</div>
		</div>
	</div>
</div>
<div id="crud" class="crud {if $fluid_container == true}container-fluid{else}container{/if}">
{if $header != ''}
	{$header}
{/if}
{if sizeof($header_buttons) > 0}
	<div class="row">
		<div class="col-md-12">
			<div class="printer-hidden">
				<div class="btn-group">
{foreach item=button from=$header_buttons}
					{$button}
{/foreach}
				</div>
				{if isset($module) && $module == 'backups'}
				<p>({t}For pricing and more information{/t}: <a style="color: #004085;" target="_blank" href="https://www.interserver.net/backups/">https://www.interserver.net/backups/</a>)</p>
				{/if}
			</div>
		</div>
	</div>
{/if}
	<div class="row">
		<div class="col-md-12">

		{if $refresh_button == true}
			<div class="refresh-container"><i class="refresh-spinner fa fa-spinner fa-spin fa-2x"></i></div>
{/if}
			<div class="table-responsive">
				<table id="crud-table" class="crud-table table table-bordred table-striped table-hover table-condensed">
{if isset($title) || isset($table_headers)}
					<thead class="">
{if isset($title)}
						<tr>
							<th colspan="{$titcolspan}">
{if sizeof($title_buttons) > 0}
								<div class="crud-header-buttons pull-left printer-hidden">
									<div class="btn-group">
{foreach item=button from=$title_buttons}
										{$button}
{/foreach}
									</div>
								</div>
{/if}
								<span class="crud-title">{$title}</span>
{if $print_button == true || $export_button == true}
								<div class="export btn-group pull-right printer-hidden">
{if $print_button == true}
									<button class="btn btn-sm btn-default" type="button" title="Print" onClick="crud_print();">
										<i class="fa fa-print crud-icon"></i>
										{t}Print{/t}
									</button>
{/if}
{if $export_button == true}
									<button class="btn btn-sm btn-default dropdown-toggle" type="button" title="Export data" data-toggle="dropdown" aria-expanded="false">
										<i class="fa fa-download crud-icon"></i>
										{t}Export{/t}
										<span class="caret"></span>
										<span class="sr-only">{t}Toggle Dropdown{/t}</span>
									</button>
									<ul class="dropdown-menu" role="menu">
{foreach item=format_data key=ext from=$export_formats}
										<li role="presentation" data-type="{$ext}">
											<a href="#" data-container="body" data-toggle="tooltip" title="{$format_data.name}"  onClick="crud_export(this); this.preventDefault();">
												<img src="/images/crud/{$ext}.png" alt=""> {$ext|strtoupper}
											</a>
										</li>
{/foreach}
									</ul>
{/if}
								</div>
{/if}
							</th>
						</tr>
{/if}
{if isset($table_headers)}
{section name=itemrow loop=$table_headers}
						<tr {$table_headers[itemrow].rowopts}>
{if $select_multiple == true}
							<th><input type="checkbox" id="checkall" /></th>
{/if}
{section name=itemcol loop=$table_headers[itemrow].cols}
							<th colspan="{$table_headers[itemrow].cols[itemcol].colspan}" bgcolor="{$table_headers[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_headers[itemrow].cols[itemcol].colalign};" {$table_headers[itemrow].cols[itemcol].colopts}>
								<span role="button" class="header_link" onClick="crud_update_sort(this);">
									{$table_headers[itemrow].cols[itemcol].text}
								</span>
							</th>
{/section}
{if isset($row_buttons)}
							<th></th>
{/if}
						</tr>
{/section}
{/if}
{/if}
					</thead>
					<tbody>
{section name=itemrow loop=$table_rows}
						<tr {$table_rows[itemrow].rowopts}>
{if $select_multiple == true}
							<td><input type="checkbox" class="checkthis" /></td>
{/if}
{section name=itemcol loop=$table_rows[itemrow].cols}
							<td colspan="{$table_rows[itemrow].cols[itemcol].colspan}" bgcolor="{$table_rows[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_rows[itemrow].cols[itemcol].colalign};" {if isset($table_rows[itemrow].cols[itemcol].colopts)}{$table_rows[itemrow].cols[itemcol].colopts}{/if}>
{assign var=value value=$table_rows[itemrow].cols[itemcol].text}
{if $value|in_array:$label_rep}
								<span class="label label-sm label-{$label_rep.$value}">{$value}</span>
{else}
								{$value}
{/if}
							</td>
{/section}
{if isset($row_buttons)}
							<td>
{foreach item=button_row from=$row_buttons}
								{$button_row}
{/foreach}
							</td>
{/if}
						</tr>
{/section}
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<div class="row">
		<form accept-charset="UTF-8" role="form" id="paginationForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=list{$extra_url_args}" autocomplete="on" method="GET">
		{if $total_pages > 1}
		<div class="col-md-12 crud-nav-bar">
			<div class="nav-crud">
				<ul class="pagination">
					<li id="crud-pager-prev" class="{if $page == 1}disabled{/if}"><a href=""><span class="glyphicon glyphicon-chevron-left"></span></a></li>
{foreach item=pager from=$page_links}
					<li class="crud-page {if $pager == $page}active{/if}"><a href="" class="" data-offset="{($pager - 1) * $page_limit}">{$pager}</a></li>
{/foreach}
					<li id="crud-pager-next" class="{if $page >= $total_pages}disabled{/if}"><a href=""><span class="glyphicon glyphicon-chevron-right"></span></a></li>
				</ul>
				<div class="btn-group row-counts nav-rows " role="group"  aria-label="{t}Rows Per Page{/t}">
{foreach from=$page_limits item=$limit}
{if $limit <= $total_rows}
					<button type="button" class="btn btn-default {if $page_limit == $limit}active{/if}" data-limit="{$limit}">{if $limit == -1}{t}All{/t}{else}{$limit}{/if}</button>
{/if}
{/foreach}
				</div>
				<a id="crud-search" class="btn btn-sm btn-primary crud-search" href="" title="Search" data-tile="Search">
					<span class="fa fa-search fa-fw"></span> {t}Search{/t}
				</a>
				<span id="crud-search-more" class="crud-search form-inline" style="display: none;">
					<input class="crud-searchdata crud-search-active input-small form-control" name="search" data-type="text" type="text" value="">
					<select class="crud-daterange crud-searchdata input-small form-control" name="range" data-fieldtype="date" style="display:none; ">
						<option value="">- choose range -</option>
						<option value="next_year" data-from="" data-to="">{t}Next Year{/t}</option>
						<option value="next_month" data-from="" data-to="">{t}Next Month{/t}</option>
						<option value="today" data-from="" data-to="">{t}Today{/t}</option>
						<option value="this_week_today" data-from="" data-to="">{t}This Week up to today{/t}</option>
						<option value="this_week_full" data-from="" data-to="">{t}This full Week{/t}</option>
						<option value="last_week" data-from="" data-to="">{t}Last Week{/t}</option>
						<option value="last_2weeks" data-from="" data-to="">{t}Last two Weeks{/t}</option>
						<option value="this_month" data-from="" data-to="">{t}This Month{/t}</option>
						<option value="last_month" data-from="" data-to="">{t}Last Month{/t}</option>
						<option value="last_3months" data-from="" data-to="">{t}Last 3 Months{/t}</option>
						<option value="last_6months" data-from="" data-to="">{t}Last 6 Months{/t}</option>
						<option value="this_year" data-from="" data-to="">{t}This Year{/t}</option>
						<option value="last_year" data-from="" data-to="">{t}Last Year{/t}</option>
					</select>
					<input class="crud-searchdata crud-datepicker-from input-small form-control" name="date_from" style="display:none; " data-type="datetime" data-fieldtype="date" type="text" value="">
					<input class="crud-searchdata crud-datepicker-to input-small form-control" name="date_to" style="display:none; " data-type="datetime" data-fieldtype="date" type="text" value="">
					<select class="crud-data crud-columns-select input-small form-control" name="column" id="crud_search_column">
						<option value="">{t}All fields{/t}</option>
{foreach from=$labels key=idx item=value}
						<option value="{$idx}" data-type="int">{$value}</option>
{/foreach}
<!--						<option value="{$idx}" data-type="text">{t}Check number{/t}</option>
						<option value="{$idx}" data-type="datetime">{t}Payment date{/t}</option>
						<option value="{$idx}" data-type="float">{t}Amount{/t}</option> -->
					</select>
					<span class="btn-group">
						<a class="btn btn-sm btn-primary" href="" data-search="1" id="crud_search_button">{t}Go{/t}</a>
					</span>
				</span>
{if $admin == true || $refresh_button == true}
				<span class="btn-group nav-rows">
{if $admin == true}
					<a class="btn btn-sm btn-warning" href="" data-toggle="modal" data-target="#debugModal" title="{t}Debug Output{/t}" data-title="{t}Debug Output{/t}" >
						<span class="fa fa-bug fa-fw"></span>
					</a>
{/if}
{if $refresh_button == true}
					<a class="btn btn-sm btn-info refresh" href="" title="{t}Refresh Table{/t}" data-title="{t}Refresh Table{/t}" >
						<span class="fa fa-refresh fa-fw"></span>
					</a>
{/if}
				</span>
{/if}
			</div>
		</div>
		{/if}
		</form>
	</div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<form accept-charset="UTF-8" role="form" id="editModalForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=edit{$extra_url_args}" autocomplete="on" method="POST" enctype="multipart/form-data">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="{t}Close{/t}"><span aria-hidden="true">&times;</span></button>
				<!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button> -->
				<h4 class="modal-title custom_align" id="editModalLabel">{t}Edit{/t} {$title} {t}Details{/t}</h4>
			</div>
			<div class="modal-body">
				{$edit_form}
				<div class="error_message"></div>
			</div>
			<div class="modal-footer ">
				<button type="submit" id="editModalUpdateButton" class="btn btn-primary btn-lg" ><span class="glyphicon glyphicon-ok-sign"></span> {t}Update{/t}</button>
				<button type="button" id="editModalCancelButton" class="btn btn-danger btn-lg" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> {t}Cancel{/t}</button>
			</div>
			</form>
		</div>
	</div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<form accept-charset="UTF-8" role="form" id="deleteModalForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=delete{$extra_url_args}" autocomplete="on" method="POST" enctype="multipart/form-data">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="deleteModalLabel">{t}Delete this entry{/t}</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label class="col-md-offset-1 col-md-4 control-label" for="primary_key">{t}ID{/t}</label>
					<div class="form-group input-group col-md-6">
						<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
						<input type="text" class="form-control" disabled="disabled" name="primary_key" id="primary_key" value="" placeholder="" autocomplete="off" style="width: 100%;">
					</div>
				</div>
				<div class="error_message" style="text-align: left;"></div>
				<div class="alert alert-danger"><span class="glyphicon glyphicon-warning-sign"></span> {t}Are you sure you want to delete this Record?{/t}</div>
			</div>
			<div class="modal-footer ">
				<button type="submit" class="btn btn-success" ><span class="glyphicon glyphicon-ok-sign"></span> {t}Yes{/t}</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> {t}No{/t}</button>
			</div>
		</div>
		</form>
	</div>
</div>
{if $admin == true}
<div class="modal fade" id="debugModal" tabindex="-1" role="dialog" aria-labelledby="debugModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="debugModalLabel">{t}Admin Debug Output{/t}</h4>
			</div>
			<div class="modal-body">
				<pre style="text-align: left; overflow: scroll; max-height: 600px;">{$debug_output}</pre>
			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> {t}No{/t}</button>
			</div>
		</div>
	</div>
</div>
{/if}
<script>
	var crud_rows = {$rows|json_encode};
	var crud_primary_key = "{$primary_key}";
	var crud_page_offset = {$page_offset};
	var crud_page_limit = {$page_limit};
	var crud_order_dir = "{$order_dir}";
	var crud_order_by = "{$order_by}";
	var crud_total_pages = {$total_pages};
	var crud_page = {$page};
	var crud_search_terms = [];
	var crud_total_count = "{$total_rows}";
</script>
<script src="/js/crud.js"></script>
<link rel="stylesheet" href="/css/crud_table5.css">
