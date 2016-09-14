<div class="container">
	<div class="row">
		<div class="col-md-12">
			<div class="table-responsive">
				<table id="mytable" class="table table-bordred table-striped table-hover table-condensed">
{if isset($title) || isset($table_headers)}
					<thead class=">
{if isset($title)}
						<tr>
							<th><input type="checkbox" id="checkall" /></th>
							<th style="text-align:center;" colspan={$titcolspan + 3}>
								{$title}
							</th>
						</tr>
{/if}
{if isset($table_headers)}
{section name=itemrow loop=$table_headers}
						<tr {$table_headers[itemrow].rowopts}>
							<th><input type="checkbox" id="checkall" /></th>
{section name=itemcol loop=$table_headers[itemrow].cols}
							<th colspan="{$table_headers[itemrow].cols[itemcol].colspan}" bgcolor="{$table_headers[itemrow].cols[itemcol].colbgcolor}" style="text-align:{$table_headers[itemrow].cols[itemcol].colalign};" {$table_headers[itemrow].cols[itemcol].colopts}>
								{$table_headers[itemrow].cols[itemcol].text}
							</th>
{/section}
							<th colspan=2>&nbsp;</th>
						</tr>
{/section}
{/if}
{/if}
					</thead>
					<tbody>
{section name=itemrow loop=$table_rows}
						<tr {$table_rows[itemrow].rowopts}>
							<td><input type="checkbox" class="checkthis" /></td>
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
							<td><p data-placement="top" data-toggle="tooltip" title="Edit"><button class="btn btn-primary btn-xs" data-title="Edit" data-toggle="modal" data-target="#edit" ><span class="glyphicon glyphicon-pencil"></span></button></p></td>
							<td><p data-placement="top" data-toggle="tooltip" title="Delete"><button class="btn btn-danger btn-xs" data-title="Delete" data-toggle="modal" data-target="#delete" ><span class="glyphicon glyphicon-trash"></span></button></p></td>
						</tr>
{/section}
					</tbody>
				</table>
				<div class="clearfix"></div>
				<ul class="pagination pull-right">
					<li class="disabled"><a href="#"><span class="glyphicon glyphicon-chevron-left"></span></a></li>
					<li class="active"><a href="#">1</a></li>
					<li><a href="#">2</a></li>
					<li><a href="#">3</a></li>
					<li><a href="#">4</a></li>
					<li><a href="#">5</a></li>
					<li><a href="#"><span class="glyphicon glyphicon-chevron-right"></span></a></li>
				</ul>
			</div>
		</div>
	</div>
</div>
<div class="modal fade" id="edit" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="Heading">Edit Your Detail</h4>
			</div>
			<div class="modal-body">
				<div class="form-group">
					<input class="form-control " type="text" placeholder="Mohsin">
				</div>
				<div class="form-group">

					<input class="form-control " type="text" placeholder="Irshad">
				</div>
				<div class="form-group">
					<textarea rows="2" class="form-control" placeholder="CB 106/107 Street # 11 Wah Cantt Islamabad Pakistan"></textarea>


				</div>
			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-warning btn-lg" style="width: 100%;"><span class="glyphicon glyphicon-ok-sign"></span> Update</button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
<div class="modal fade" id="delete" tabindex="-1" role="dialog" aria-labelledby="edit" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><span class="glyphicon glyphicon-remove" aria-hidden="true"></span></button>
				<h4 class="modal-title custom_align" id="Heading">Delete this entry</h4>
			</div>
			<div class="modal-body">

				<div class="alert alert-danger"><span class="glyphicon glyphicon-warning-sign"></span> Are you sure you want to delete this Record?</div>

			</div>
			<div class="modal-footer ">
				<button type="button" class="btn btn-success" ><span class="glyphicon glyphicon-ok-sign"></span> Yes</button>
				<button type="button" class="btn btn-default" data-dismiss="modal"><span class="glyphicon glyphicon-remove"></span> No</button>
			</div>
		</div>
		<!-- /.modal-content -->
	</div>
	<!-- /.modal-dialog -->
</div>
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