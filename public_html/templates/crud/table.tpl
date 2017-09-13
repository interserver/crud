<table class="table table-bordered table-hover" {$tableopts}>
{if isset($title) || isset($table_headers)}
	<thead class="">
{if isset($title)}
		<tr>
			<th style="text-align: center;" colspan={$titcolspan}>
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
	<tbody class="">
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
		</tr>
{/section}
{if isset($footer)}
		<tr>
			<td colspan={$titcolspan}>
				{$footer}
			</td>
		</tr>
{/if}
	</tbody>
</table>
