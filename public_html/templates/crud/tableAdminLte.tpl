<link rel="stylesheet" href="/templates/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
<div class="row">
    <div class="col-md-12">
        {if isset($module) && $module == 'backups'}
        <div class="alert alert-default">({t}For pricing and more information{/t}: <a style="color: #004085;" target="_blank" href="https://www.interserver.net/backups/">https://www.interserver.net/backups/</a>)</div>
        {/if}
        <div class="card">
            <div class="card-header text-right">
                <div class="row float-right">
                    {if $total_pages > 1}
                    <div id="search_btns" class="col-md-auto printer-hidden text-right pl-2">
                        <form accept-charset="UTF-8" role="form" id="paginationForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=list{$extra_url_args}" autocomplete="on" method="GET">
                            <a id="crud-search" class="btn btn-sm btn-primary" href="" title="Search" data-tile="Search">
                                <span class="fa fa-search fa-fw"></span> {t}Search{/t}
                            </a>
                            <span id="crud-search-more" class="crud-search form-inline float-right" style="display: none;">
                                <input class="crud-searchdata crud-search-active form-control form-control-sm mr-1" name="search" data-type="text" type="text" value="">
                                <select class="crud-daterange crud-searchdata form-control form-control-sm selectpicker mr-1" name="range" data-fieldtype="date" style="display:none; ">
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
                                <input class="crud-searchdata crud-datepicker-from form-control form-control-sm mr-1" name="date_from" style="display:none; " data-type="datetime" data-fieldtype="date" type="text" value="">
                                <input class="crud-searchdata crud-datepicker-to form-control form-control-sm mr-1" name="date_to" style="display:none; " data-type="datetime" data-fieldtype="date" type="text" value="">
                                <select class="crud-data crud-columns-select form-control form-control-sm mr-1" name="column" id="crud_search_column">
                                    <option value="">{t}All fields{/t}</option>
                                    {foreach from=$labels key=idx item=value}
                                    <option value="{$idx}" data-type="int">{$value}</option>
                                    {/foreach}
                                </select>
                                <span class="btn-group">
                                    <a class="btn btn-sm btn-primary" href="" data-search="1" id="crud_search_button">{t}Go{/t}</a>
                                </span>
                            </span>
                        </form>
                    </div>
                    {/if}
                    <div id="header_btns" class="col-md-auto printer-hidden text-right pl-2">
                        <div class="btn-group">
                        {foreach item=button from=$header_buttons}
                            {$button}
                        {/foreach}
                        </div>
                    </div>
                    {if $print_button == true || $export_button == true}
                    <div id="print_expo_btns" class="col-md-auto export pull-right printer-hidden pl-2">
                        <div class="btn-group">
                        {if $print_button == true}
                            <button class="btn btn-sm btn-default" type="button" title="Print" onClick="crud_print();">
                                <i class="fa fa-print crud-icon"></i>{t}Print{/t}
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
                    </div>
                    {/if}
                    {if sizeof($title_buttons) > 0}
                    <div id="title_btns" class="col-md-auto printer-hidden pl-2">
                        <div class="btn-group">
                        {foreach item=button from=$title_buttons}
                            {$button}
                        {/foreach}
                        </div>
                    </div>
                    {/if}
                </div>
            </div>
            <!-- /.card-header -->
            <div class="card-body">
            {if $select_multiple == true}
	            {assign var=titcolspan value=$titcolspan + 1}
            {/if}
            {if isset($row_buttons)}
	            {assign var=titcolspan value=$titcolspan + 1}
            {/if}
            <div id="crud" class="crud">
            {if $header != ''}
	            {$header}
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
        <div class="col-md-6">
            <form accept-charset="UTF-8" role="form" id="paginationForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=list{$extra_url_args}" autocomplete="on" method="GET" style="display:inline-flex;">
            {if $total_pages > 1}
            
                    <div class="btn-group row-counts" role="group"  aria-label="{t}Rows Per Page{/t}">
    {foreach from=$page_limits item=$limit}
    {if $limit <= $total_rows}
                        <button type="button" class="btn btn-default btn-sm {if $page_limit == $limit}active{/if}" data-limit="{$limit}">{if $limit == -1}{t}All{/t}{else}{$limit}{/if}</button>
    {/if}
    {/foreach}
                    </div>
                    
    {if $admin == true || $refresh_button == true}
                    <span class="btn-group nav-rows">
    {if $admin == true}
                        <a class="btn btn-sm btn-warning" href="" data-toggle="modal" data-target="#debugModal" title="{t}Debug Output{/t}" data-title="{t}Debug Output{/t}" >
                            <span class="fa fa-bug fa-fw"></span>
                        </a>
    {/if}
    {if $refresh_button == true}
                        <a class="btn btn-sm btn-info refresh" href="" title="{t}Refresh Table{/t}" data-title="{t}Refresh Table{/t}" >
                            <span class="fas fa-sync fa-fw"></span>
                        </a>
    {/if}
                    </span>
    {/if}
            {/if}
            </form>
        </div>
        <div class="col-md-6 float-right">
            {if $total_pages > 1}
            <nav aria-label="Page navigation float-right" class="crud">
                <ul class="pagination justify-content-end">
                    <li id="crud-pager-prev" class="page-item {if $page == 1}disabled{/if}">
                        <a class="page-link" href="javascript:void(0);" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                            <span class="sr-only">Previous</span>
                        </a>
                    </li>
                    {foreach item=pager from=$page_links}
                    <li class="page-item crud-page {if $pager == $page}active{/if}"><a class="page-link" href="" data-offset="{($pager - 1) * $page_limit}">{$pager}</a></li>
                    {/foreach}
                    <li id="crud-pager-next" class="page-item {if $page >= $total_pages}disabled{/if}">
                    <a class="page-link" href="#" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                        <span class="sr-only">Next</span>
                    </a>
                    </li>
                </ul>
            </nav>
            {/if}
        </div>
    </div>
</div>
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
<script src="/templates/adminlte/plugins/datatables/jquery.dataTables.js"></script>
<script src="/templates/adminlte/plugins/datatables-bs4/js/dataTables.bootstrap4.js"></script>
<script>
jQuery(function() {
	$('#title_btns > .btn-group > a.active').trigger('click');
    $('#title_btns > .btn-group > a').on('click', function(){
        $('#title_btns > .btn-group > a').each(function(){
            $(this).removeClass('active');
        });
        $(this).addClass('active');
    });
    $('.row-counts > .active').addClass('btn-primary');
    $('.row-counts > .btn').on('click', function(){
        $(this).addClass('btn-primary');
    });
});
</script>
<style>
a.btn-info:link, a.btn-info:active, a.btn-info:visited, a.btn-info:hover {
    font-size: inherit;
}
div.dataTables_length label, div.dataTables_filter label {
    text-align: left;
    white-space: nowrap;
}
div.dataTables_filter {
    text-align: right;
}
</style>


            </div>
        </div>
    </div>
</div>