#!/usr/bin/php
<?php
require_once(__DIR__ . '/../../include/functions.inc.php');
add_js('bootstrap');
require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
$cmd = "ls " . INCLUDE_ROOT . "/forms/*json;";
$files = explode("\n", trim(`$cmd`));
$function_req = '';
$function_req .= "			'cruds' => '/crud/cruds.php',\n";
$li = [];
$li[] = "<a href='#' class='list-group-item active'>CRUD Page Links</a>";
foreach ($files as $filepath) {
	$func = basename($filepath, '.json');
	$data = json_decode(file_get_contents($filepath), TRUE);
	$title = $data['caption'];
	$query = "select {$data['queryfields']} {$data['query']}";
	$function_req .= "			'crud_{$func}' => '/crud/crud_{$func}.php',\n";
	$li[] = "<a href='?choice=none.crud_{$func}' class='list-group-item'>
<span class='label label-info'>{$func}</span> - {$title}</a>";
	file_put_contents(INCLUDE_ROOT . '/crud/crud_' . $func . '.php', '<'.'?'."php
function crud_{$func}() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	\$crud = crud::init(\"{$query}\"" . (in_array($data['module'], array('innertell', 'mb', 'powerdns')) ? ", '{$data['module']}'" : '') . ")
		->set_title(\"{$title}\")
		->go();
}
");
file_put_contents(INCLUDE_ROOT . '/crud/cruds.php', '<'.'?'."php
function cruds() {
	add_js('bootstrap');
	add_output(\"<div class='list-group' style='width: 500px; text-align: left;'>\n" . implode("\n", $li)."\n</div>\n\");
}");
file_put_contents(INCLUDE_ROOT . '/function_requirements.new', $function_req);
}
