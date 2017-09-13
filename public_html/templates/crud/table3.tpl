{literal}
<style type="text/css">
	.trash { color: #d15b47; }
	.flag { color: #f89406; }
	.panel-body { padding:0; }
	.panel-footer .pagination { margin: 0; }
	.panel .glyphicon,.list-group-item .glyphicon { margin-right:5px; }
	.panel-body .radio, .checkbox { display:inline-block;margin:0; }
	.panel-body input[type=checkbox]:checked + label { text-decoration: line-through;color: #8090a0; }
	.list-group-item:hover, a.list-group-item:focus {text-decoration: none;background-color: #f5f5f5;}
	.list-group { margin-bottom:0; }
	.action-buttons{margin-right:5px;}
</style>
{/literal}
<div class="container">
	<div class="row">
		<div class="col-md-6">
			<div class="panel panel-primary">
				<div class="panel-heading">
{if isset($title)}
					{$title}
{/if}
					<div class="btn-group pull-right">
						<button class="btn btn-primary btn-xs panel-reset-settings" type="button" title="Reset Panel Settings to Default State">
							<i class="glyphicon glyphicon-off"></i>
						</button>
						<button class="panel-collapse btn btn-primary btn-xs" title="Expand or Collapse Panel">
							<i class="glyphicon glyphicon-chevron-down"></i>
						</button>
					</div>
					<div class="pull-right action-buttons">
						<div class="btn-group pull-right">
							<button type="button" class="btn btn-default btn-xs dropdown-toggle" data-toggle="dropdown">
								<span class="glyphicon glyphicon-list"></span>
							</button>
							<ul class="dropdown-menu slidedown">
								<li><a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span>{t}Edit{/t}</a></li>
								<li><a href="//www.interserver.net"><span class="glyphicon glyphicon-trash"></span>{t}Delete{/t}</a></li>
								<li><a href="//www.interserver.net"><span class="glyphicon glyphicon-flag"></span>{t}Flag{/t}</a></li>
							</ul>
						</div>
					</div>

				</div>
				<div class="panel-body">
					<ul class="list-group">
						<li class="list-group-item">
							<div class="checkbox">
								<input type="checkbox" id="checkbox" />
								<label for="checkbox">
									List group item heading
								</label>
							</div>
							<div class="pull-right action-buttons">
								<a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span></a>
								<a href="//www.interserver.net" class="trash"><span class="glyphicon glyphicon-trash"></span></a>
								<a href="//www.interserver.net" class="flag"><span class="glyphicon glyphicon-flag"></span></a>
							</div>
						</li>
						<li class="list-group-item">
							<div class="checkbox">
								<input type="checkbox" id="checkbox2" />
								<label for="checkbox2">
									List group item heading 1
								</label>
							</div>
							<div class="pull-right action-buttons">
								<a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span></a>
								<a href="//www.interserver.net" class="trash"><span class="glyphicon glyphicon-trash"></span></a>
								<a href="//www.interserver.net" class="flag"><span class="glyphicon glyphicon-flag"></span></a>
							</div>
						</li>
						<li class="list-group-item">
							<div class="checkbox">
								<input type="checkbox" id="checkbox3" />
								<label for="checkbox3">
									List group item heading 2
								</label>
							</div>
							<div class="pull-right action-buttons">
								<a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span></a>
								<a href="//www.interserver.net" class="trash"><span class="glyphicon glyphicon-trash"></span></a>
								<a href="//www.interserver.net" class="flag"><span class="glyphicon glyphicon-flag"></span></a>
							</div>
						</li>
						<li class="list-group-item">
							<div class="checkbox">
								<input type="checkbox" id="checkbox4" />
								<label for="checkbox4">
									List group item heading 3
								</label>
							</div>
							<div class="pull-right action-buttons">
								<a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span></a>
								<a href="//www.interserver.net" class="trash"><span class="glyphicon glyphicon-trash"></span></a>
								<a href="//www.interserver.net" class="flag"><span class="glyphicon glyphicon-flag"></span></a>
							</div>
						</li>
						<li class="list-group-item">
							<div class="checkbox">
								<input type="checkbox" id="checkbox5" />
								<label for="checkbox5">
									List group item heading 4
								</label>
							</div>
							<div class="pull-right action-buttons">
								<a href="//www.interserver.net"><span class="glyphicon glyphicon-pencil"></span></a>
								<a href="//www.interserver.net" class="trash"><span class="glyphicon glyphicon-trash"></span></a>
								<a href="//www.interserver.net" class="flag"><span class="glyphicon glyphicon-flag"></span></a>
							</div>
						</li>
					</ul>
				</div>
				<div class="panel-footer">
					<div class="row">
						<div class="col-md-6">
							<h6>
								Total Count <span class="label label-info">25</span></h6>
						</div>
						<div class="col-md-6">
							<ul class="pagination pagination-sm pull-right">
								<li class="disabled"><a href="javascript:void(0)">«</a></li>
								<li class="active"><a href="javascript:void(0)">1 <span class="sr-only">(current)</span></a></li>
								<li><a href="//www.interserver.net">2</a></li>
								<li><a href="//www.interserver.net">3</a></li>
								<li><a href="//www.interserver.net">4</a></li>
								<li><a href="//www.interserver.net">5</a></li>
								<li><a href="javascript:void(0)">»</a></li>
							</ul>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
