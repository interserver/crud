{literal}
<style>
	.filterable {
		margin-top: 15px;
	}
	.filterable .card-header .float-right {
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
		<div class="card bg-primary text-white filterable">
			<div class="card-header">
{if isset($title)}
				<h3 class="card-title">{$title}</h3>
{/if}
				<div class="float-right">
					<button class="btn btn-secondary btn-xs btn-filter"><span class="fa fa-filter"></span> {t}Filter{/t}</button>
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
						<td colspan="{$table_rows[itemrow].cols[itemcol].colspan}" bgcolor="{$table_rows[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_rows[itemrow].cols[itemcol].colalign};" {if isset($table_rows[itemrow].cols[itemcol].colopts)}{$table_rows[itemrow].cols[itemcol].colopts}{/if}>
{assign var=value value=$table_rows[itemrow].cols[itemcol].text}
{if $value|in_array:$label_rep}
							<span class="label label-sm label-{$label_rep.$value}">{$value}</span>
{else}
							{$value}
{/if}
						</td>
{/section}
						<td>
							<a href="#" style="padding-right: 10px;" title="Edit User"><i class="fa fa-edit color-blue"></i></a>
							<a href="#" style="padding-right: 10px;" title="Account History"><i class="fa fa-clock-o color-green"></i></a>
							<a href="#" style="padding-right: 10px;" title="Delete User"><i class="fa fa-times-circle color-red"></i></a>
						</td>
					</tr>
{/section}
				</tbody>
			</table>
		</div>
	</div>
</div>
{literal}
<script>
	/*
	Please consider that the JS part isn't production ready at all, I just code it to show the concept of merging filters and titles together !
	*/
	$(document).ready(function(){
		$('.filterable .btn-filter').click(function(){
			var $panel = $(this).parents('.filterable'),
			$filters = $panel.find('.filters input'),
			$tbody = $panel.find('.table tbody');
			if ($filters.prop('disabled') == true) {
				$filters.prop('disabled', FALSE);
				$filters.first().focus();
			} else {
				$filters.val('').prop('disabled', TRUE);
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
				$table.find('tbody').prepend($('<tr class="no-result text-center"><td colspan="'+ $table.find('.filters th').length +'">{t}No result found{/t}</td></tr>'));
			}
		});
	});
</script>
{/literal}