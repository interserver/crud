{literal}
<style type="text/css">
/*	ul.pagination li a {
		height: 30px;
	}*/
</style>
{/literal}
{if $select_multiple == true}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
{if $edit_row == true}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
{if $delete_row == true}
	{assign var=titcolspan value=$titcolspan + 1}
{/if}
<div class="crud container" style="margin-bottom: 10px;">
	<div class="row">
		<div class="col-md-12">
			<div class="table-responsive">
				<table id="mytable" class="table table-bordred table-striped table-hover table-condensed">
{if isset($title) || isset($table_headers)}
					<thead class="">
{if isset($title)}
						<tr>
							<th style="text-align:center;" colspan="{$titcolspan}">
								{$title}
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
								{$table_headers[itemrow].cols[itemcol].text}
							</th>
{/section}
{if $edit_row == true}
							<th></th>
{/if}
{if $delete_row == true}
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
{if $edit_row == true}
							<td>
								<button type="button" class="btn btn-primary btn-xs" onclick="edit_form(this);" title="Edit">
									<i class="fa fa-fw fa-pencil"></i>
								</button>
							</td>
{/if}
{if $delete_row == true}
							<td>
								<button type="button" class="btn btn-danger btn-xs" onclick="delete_form(this);" title="Delete">
									<i class="fa fa-fw fa-trash"></i>
								</button>
							</td>
{/if}
						</tr>
{/section}
					</tbody>
				</table>
			</div>
		</div>
	</div>
{if $total_pages > 1}
	<div class="row">
		<form accept-charset="UTF-8" role="form" id="paginationForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=list" autocomplete="on" method="GET">
		<div class="col-md-12" style="display: table;">
			<div class="nav-crud" style="display: table-row; vertical-align: top;">
				<ul class="pagination " style="margin: 0px; display: table-cell; vertical-align: top;">
{if $page > 1}
					<li class=""><a href="#" style="height: 36px;"><span class="glyphicon glyphicon-chevron-left"></span></a></li>
{else}
					<li class="disabled"><a href="#" style="height: 36px;"><span class="glyphicon glyphicon-chevron-left"></span></a></li>
{/if}
{foreach item=pager from=$page_links}
					<li class="{if $pager == $page}active{/if}"><a href="" class="" data-offset="{$pager * $page_limit}">{$pager}</a></li>
{/foreach}
{if $page < $total_pages}
					<li><a href="#" style="height: 36px;"><span class="glyphicon glyphicon-chevron-right"></span></a></li>
{/if}
				</ul>
				<div class="btn-group row-counts nav-rows " role="group" style="display: table-cell; vertical-align: top;" aria-label="Rows Per Page">
{foreach from=$page_limits item=$limit}
{if $limit <= $total_rows}
					<button type="button" class="btn btn-default {if $page_limit == $limit}active{/if}" data-limit="{$limit}">{if $limit == -1}All{else}{$limit}{/if}</button>
{/if}
{/foreach}
				</div>
				<a id="crud-search" class="btn btn-primary crud-search" href="" style="vertical-align: top;" title="Search" data-tile="Search">
					<i class="fa fa-search fa-lg"></i> Search
				</a>
				<span id="crud-search-more" class="crud-search form-inline" style="display: none;">
					<input class="crud-searchdata crud-search-active input-small form-control" name="search" data-type="text" style="" type="text" value="">
					<select class="crud-daterange crud-searchdata input-small form-control" name="range" data-fieldtype="date" style="display:none">
						<option value="">- choose range -</option>
						<option value="next_year" data-from="" data-to="">Next Year</option>
						<option value="next_month" data-from="" data-to="">Next Month</option>
						<option value="today" data-from="" data-to="">Today</option>
						<option value="this_week_today" data-from="" data-to="">This Week up to today</option>
						<option value="this_week_full" data-from="" data-to="">This full Week</option>
						<option value="last_week" data-from="" data-to="">Last Week</option>
						<option value="last_2weeks" data-from="" data-to="">Last two Weeks</option>
						<option value="this_month" data-from="" data-to="">This Month</option>
						<option value="last_month" data-from="" data-to="">Last Month</option>
						<option value="last_3months" data-from="" data-to="">Last 3 Months</option>
						<option value="last_6months" data-from="" data-to="">Last 6 Months</option>
						<option value="this_year" data-from="" data-to="">This Year</option>
						<option value="last_year" data-from="" data-to="">Last Year</option>
					</select>
					<input class="crud-searchdata crud-datepicker-from  input-small form-control" name="date_from" style="display:none" data-type="datetime" data-fieldtype="date" type="text" value="">
					<input class="crud-searchdata crud-datepicker-to  input-small form-control" name="date_to" style="display:none" data-type="datetime" data-fieldtype="date" type="text" value="">
					<select class="crud-data crud-columns-select input-small form-control" name="column">
						<option value="">All fields</option>
{foreach from=$labels key=idx item=value}
						<option value="{$idx}" data-type="int">{$value}</option>
{/foreach}
<!--						<option value="{$idx}" data-type="text">Checknumber</option>
						<option value="{$idx}" data-type="datetime">Paymentdate</option>
						<option value="{$idx}" data-type="float">Amount</option> -->
					</select>
					<span class="btn-group">
						<a class="btn btn-primary" href="" data-search="1">Go</a>
					</span>
				</span>
{if $ima == 'admin'}
				<span class="btn-group nav-rows" style=" display: table-cell; vertical-align: top;">
					<a class="btn btn-info" href="" data-toggle="modal" data-target="#debugModal" title="Debug Output" data-title="Debug Output" >
						<i class="fa fa-bug" style="font-size: 20px;"></i>
					</a>
				</span>
{/if}
			</div>
		</div>
		</form>
	</div>
</div>
{/if}
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<form accept-charset="UTF-8" role="form" id="editModalForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=edit" autocomplete="on" method="POST" enctype="multipart/form-data">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<!-- <button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button> -->
				<h4 class="modal-title custom_align" id="editModalLabel">Edit {$title} Details</h4>
			</div>
			<div class="modal-body">
				{$edit_form}
				<div class="error_message" style="text-align: left;"></div>
			</div>
			<div class="modal-footer ">
				<button type="submit" id="editModalUpdateButton" class="btn btn-primary btn-lg" ><span class="glyphicon glyphicon-ok-sign"></span> Update</button>
				<button type="button" id="editModalCancelButton" class="btn btn-danger btn-lg" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> Cancel</button>
			</div>
			</form>
		</div>
	</div>
</div>
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
	<div class="modal-dialog">
		<form accept-charset="UTF-8" role="form" id="deleteModalForm" class="" action="ajax.php?choice=crud&crud={$choice}&action=delete" autocomplete="on" method="POST" enctype="multipart/form-data">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="deleteModalLabel">Delete this entry</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<label class="col-md-offset-1 col-md-4 control-label" for="primary_key">ID</label>
					<div class="form-group input-group col-md-6">
						<span class="input-group-addon"><i class="fa fa-fw fa-info"></i></span>
						<input type="text" class="form-control" disabled="disabled" name="primary_key" id="primary_key" value="" placeholder="" autocomplete="off" style="width: 100%;">
					</div>
				</div>
				<div class="error_message" style="text-align: left;"></div>
				<div class="alert alert-danger"><span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete this Record?</div>
			</div>
			<div class="modal-footer ">
				<button type="submit" class="btn btn-success" ><span class="glyphicon glyphicon-ok-sign"></span> Yes</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> No</button>
			</div>
		</div>
		</form>
	</div>
</div>
{if $ima == 'admin'}
<div class="modal fade" id="debugModal" tabindex="-1" role="dialog" aria-labelledby="debugModalLabel" aria-hidden="true">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="debugModalLabel">Debug Output</h4>
			</div>
			<div class="modal-body">
				<pre style="text-align: left;">
				{$debug_output}
				</pre>
			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> No</button>
			</div>
		</div>
	</div>
</div>
{/if}
{literal}
<script type="text/javascript">
	$(document).ready(function(){
		$("#mytable #checkall").click(function () {
			if ($("#mytable #checkall").is(':checked')) {
				$("#mytable input[type=checkbox]").each(function () {
					$(this).prop("checked", true);
				});

			} else {
				$("#mytable input[type=checkbox]").each(function () {
					$(this).prop("checked", false);
				});
			}
		});

		$("[data-toggle=tooltip]").tooltip();
	});
</script>
{/literal}