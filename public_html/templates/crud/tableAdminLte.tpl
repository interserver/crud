<link rel="stylesheet" href="/templates/adminlte/plugins/datatables-bs4/css/dataTables.bootstrap4.css">
<div class="row">
    <div class="col-md-12">
        {if isset($module) && $module == 'backups'}
        <div class="alert alert-default">({t}For pricing and more information{/t}: <a style="color: #004085;" target="_blank" href="https://www.interserver.net/backups/">https://www.interserver.net/backups/</a>)</div>
        {/if}
        <div class="card">
            <div class="card-header text-right">
                
                <span class="printer-hidden text-right pl-2">
                    <div class="btn-group">
                    {foreach item=button from=$header_buttons}
                        {$button}
                    {/foreach}
                    </div>
                </span>

                {if $print_button == true || $export_button == true}
				    <span class="export btn-group pull-right printer-hidden pl-2">
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
					</span>
                {/if}

                {if sizeof($title_buttons) > 0}
			    <span class="printer-hidden pl-2">
					<div class="btn-group">
                    {foreach item=button from=$title_buttons}
					    {$button}
                    {/foreach}
                    </div>
				</span>
                {/if}
                




            </div>

            
            


            <!-- /.card-header -->
            <div class="card-body">







            {if $select_multiple == true}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
{if isset($row_buttons)}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}

<div id="crud" class="crud {if $fluid_container == true}container-fluid{else}container{/if}">
{if $header != ''}
	{$header}
{/if}
{if sizeof($header_buttons) > 0}
	<div class="row">
		<div class="col-md-12">
			
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
  $(function () {
    var dataTables = $('#crud-table').DataTable({
      "paging": true,
      "lengthChange": true,
      "searching": true,
      "ordering": true,
      "info": true,
      "autoWidth": true,
      "deferRender": true,
      "bAutoWidth": true,
      "sScrollX": "100%",
      "bScrollCollapse": true,
      "pageLength": 100,
		  "lengthMenu": [ [10, 50, 100, 500, 1000, 2000,-1], [10, 50, 100, 500, 1000, 2000 ,"All"] ]
    });
    $('.Cfilter').on('click', function(){
    		search_string = $(this).attr("id");
		    dataTables.columns(5).search(search_string).draw();
		});
		$('.Lfilter').on('click', function(){
    		search_string = $(this).attr("id");
		    dataTables.columns(2).search(search_string).draw();
		});
  });
</script>




            </div>
        </div>
    </div>
</div>