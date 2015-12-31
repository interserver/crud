{literal}
<style type="text/css">
	.filterable {
		margin-top: 15px;
	}
	.filterable .panel-heading .pull-right {
		margin-top: -20px;
	}
	.filterable .filters input[disabled] {
		background-color: transparent;
		border: none;
		cursor: auto;
		box-shadow: none;
		padding: 0;
		height: auto;
	}
	.filterable .filters input[disabled]::-webkit-input-placeholder {
		color: #333;
	}
	.filterable .filters input[disabled]::-moz-placeholder {
		color: #333;
	}
	.filterable .filters input[disabled]:-ms-input-placeholder {
		color: #333;
	}
	.padding-left { padding-left: 10px; }
	.color-red { color: #FF0000; }
	.color-green { color: #6cbc42; }
	.color-blue { color: #0080c5; }
</style>
{/literal}
<div class="container">
	<div class="row">
		<div class="panel panel-primary filterable">
			<div class="panel-heading">
{if isset($title)}
				<h3 class="panel-title">{$title}</h3>
{/if}
				<div class="pull-right">
					<button class="btn btn-default btn-xs btn-filter"><span class="glyphicon glyphicon-filter"></span> Filter</button>
				</div>
			</div>
			<table class="table table-hover">
				<thead>
{if isset($table_headers)}
{section name=itemrow loop=$table_headers}
					<tr class="filters" {$table_headers[itemrow].rowopts}>
{section name=itemcol loop=$table_headers[itemrow].cols}
						<th colspan="{$table_headers[itemrow].cols[itemcol].colspan}" bgcolor="{$table_headers[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_headers[itemrow].cols[itemcol].colalign};" {$table_headers[itemrow].cols[itemcol].colopts}>
							<input type="text" class="form-control" placeholder="{$table_headers[itemrow].cols[itemcol].text}" disabled>
						</th>
{/section}
					</tr>
{/section}
{/if}
				</thead>
				<tbody>
{section name=itemrow loop=$table_rows}
					<tr {$table_rows[itemrow].rowopts}>
{section name=itemcol loop=$table_rows[itemrow].cols}
						<td colspan="{$table_rows[itemrow].cols[itemcol].colspan}" bgcolor="{$table_rows[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_rows[itemrow].cols[itemcol].colalign};" {$table_rows[itemrow].cols[itemcol].colopts}>
{assign var=value value=$table_rows[itemrow].cols[itemcol].text}
{if $value|in_array:$label_rep}
							<span class="label label-sm label-{$label_rep.$value}">{$value}</span>
{else}
							{$value}
{/if}
						</td>
{/section}
						<td>
							<a href="#" style="padding-right: 10px;" title="Edit User"><i class="glyphicon glyphicon-edit color-blue"></i></a>
							<a href="#" style="padding-right: 10px;" title="Account History"><i class="glyphicon glyphicon-time color-green"></i></a>
							<a href="#" style="padding-right: 10px" title="Delete User"><i class="glyphicon glyphicon-remove-sign color-red"></i></a>
						</td>
					</tr>
{/section}
				</tbody>
			</table>
		</div>
	</div>
</div>
{literal}
<script type="text/javascript">
	/*
	Please consider that the JS part isn't production ready at all, I just code it to show the concept of merging filters and titles together !
	*/
	$(document).ready(function(){
		$('.filterable .btn-filter').click(function(){
			var $panel = $(this).parents('.filterable'),
			$filters = $panel.find('.filters input'),
			$tbody = $panel.find('.table tbody');
			if ($filters.prop('disabled') == true) {
				$filters.prop('disabled', false);
				$filters.first().focus();
			} else {
				$filters.val('').prop('disabled', true);
				$tbody.find('.no-result').remove();
				$tbody.find('tr').show();
			}
		});

		$('.filterable .filters input').keyup(function(e){
			/* Ignore tab key */
			var code = e.keyCode || e.which;
			if (code == '9') return;
			/* Useful DOM data and selectors */
			var $input = $(this),
			inputContent = $input.val().toLowerCase(),
			$panel = $input.parents('.filterable'),
			column = $panel.find('.filters th').index($input.parents('th')),
			$table = $panel.find('.table'),
			$rows = $table.find('tbody tr');
			/* Dirtiest filter function ever ;) */
			var $filteredRows = $rows.filter(function(){
				var value = $(this).find('td').eq(column).text().toLowerCase();
				return value.indexOf(inputContent) === -1;
			});
			/* Clean previous no-result if exist */
			$table.find('tbody .no-result').remove();
			/* Show all rows, hide filtered ones (never do that outside of a demo ! xD) */
			$rows.show();
			$filteredRows.hide();
			/* Prepend no-result row if all rows are filtered */
			if ($filteredRows.length === $rows.length) {
				$table.find('tbody').prepend($('<tr class="no-result text-center"><td colspan="'+ $table.find('.filters th').length +'">No result found</td></tr>'));
			}
		});
	});
</script>
{/literal}