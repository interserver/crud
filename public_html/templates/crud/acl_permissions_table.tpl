<script>
	$(document).ready(function () {
		$("#permissions_check_all").on("click", function () {
			$("input.permission_checkbox").prop("checked", $(this).is(":checked"));
		});
	});
</script>
<style>
#permissions_check_all {
	margin-bottom: 5px;
}
.permission-use, .permission-use a {
font-size: 12px;
}
</style>
				<table id="permissions_table" class="table table-sm table-striped table-hover">
				<thead>
					<tr class="table-row">
						<th class="text-center">
							Permission
						</th>
						<th>
							<input type="checkbox" name="permissions_check_all" id="permissions_check_all" value="" title="Toggle all the Permissions On/Off">
						</th>
						<th class="text-left">
							Description
						</th>
					</tr>
				</thead>
				<tbody>
{foreach from=$perms item=perm}
						<tr class="table-row">
						<td class="text-right">
							<strong>{$perm.perm_name}</strong>
						</td>
						<td class="">
{if isset($role) && isset($roleperms.$role) && in_array($perm.perm_id, $roleperms.$role)}
							<input type="checkbox" name="permissions[]" id="permission_{$perm.perm_id}" value="{$perm.perm_id}" class="permission_checkbox" checked="checked">
{else}
							<input type="checkbox" name="permissions[]" id="permission_{$perm.perm_id}" value="{$perm.perm_id}" class="permission_checkbox">
{/if}
						</td>
						<td class="text-left">
							{$perm.perm_text}
						</td>
					</tr>
{if isset($perm_usage[$perm.perm_name]) && sizeof($perm_usage[$perm.perm_name]) > 0}
					<tr>
						<td colspan=3>
							<small class="permission-use">
{foreach from=$perm_usage[$perm.perm_name] item=function}
								<a href="?choice=none.{$function}" target="_blank" title="Open {$function} in a new window">{$function}</a>,
{/foreach}
							</small>
						</td>
					</tr>
{/if}
{/foreach}
				</tbody>
				</table>