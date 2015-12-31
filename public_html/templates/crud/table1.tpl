{assign var=label_rep value=[]}
{assign var=label_rep.active value='success'}
{assign var=label_rep.pending value='info'}
{assign var=label_rep.locked value='danger'}
{assign var=label_rep.suspended value='warning'}
{assign var=label_rep.canceled value='warning'}
{assign var=label_rep.expired value='danger'}
{assign var=label_rep.terminated value='danger'}
<div class="row">
	<div class="col-md-12">
		<div class="table-responsive">
			<table class="table table-bordered table-hover" {$tableopts}>
{if isset($title) || isset($table_headers)}
				<thead class=">
{if isset($title)}
					<tr>
						<th style="text-align:center;" colspan={$titcolspan}>
							{$title}
						</th>
					</tr>
{/if}
{if isset($table_headers)}
{section name=itemrow loop=$table_headers}
					<tr {$table_headers[itemrow].rowopts}>
{section name=itemcol loop=$table_headers[itemrow].cols}
						<th colspan="{$table_headers[itemrow].cols[itemcol].colspan}" bgcolor="{$table_headers[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_headers[itemrow].cols[itemcol].colalign};" {$table_headers[itemrow].cols[itemcol].colopts}>
							{$table_headers[itemrow].cols[itemcol].text}
						</th>
{/section}
					</tr>
{/section}
{/if}
				</thead>
{/if}
				<tbody>
{section name=itemrow loop=$table_rows}
					<tr {$table_rows[itemrow].rowopts}>
{section name=itemcol loop=$table_rows[itemrow].cols}
						<td colspan="{$table_rows[itemrow].cols[itemcol].colspan}" bgcolor="{$table_rows[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_rows[itemrow].cols[itemcol].colalign};" {$table_rows[itemrow].cols[itemcol].colopts}>
{assign var=value from=$table_rows[itemrow].cols[itemcol].text}
{if $value|in_array:$label_rep}
							<span class="label label-sm label-{$label_rep.$value}">{$value}</span>
{else}
							{$value}
{/if}
						</td>
{/section}
						<td>
							<a href="#" class="btn btn-success btn-xs"><span class="glyphicon glyphicon-eye-open"></span> View</a>&nbsp;
							<a class="btn btn-info btn-xs" href="#"><span class="glyphicon glyphicon-edit"></span> Edit</a>&nbsp;
							<a href="#" class="btn btn-danger btn-xs"><span class="glyphicon glyphicon-remove"></span> Delete</a>
						</td>
					</tr>
{/section}
				</tbody>
			</table>
		</div>
	</div>
	<div class="col-md-12 inline-block">
		<button type="print" class="btn btn-wide btn-primary">Print &nbsp;<i class="ti-printer"></i></button>
		<button type="export" class="btn btn-wide btn-primary">Export &nbsp;<i class="ti-export"></i></button>
	</div>
</div>
