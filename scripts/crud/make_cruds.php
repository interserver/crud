#!/usr/bin/php
<?php
require_once(__DIR__ . '/include/functions.inc.php');
require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
$cmd = "ls " . INCLUDE_ROOT . "/forms/*json;";
$files = explode("\n", trim(`$cmd`));
$function_req = '';
$function_req .= "			'cruds' => '/admin/cruds.php',\n";
$li = array();
foreach ($files as $filepath) {
	$func = basename($filepath, '.json');
	$data = json_decode(file_get_contents($filepath), true);
	$title = $data['caption'];
	$query = "select {$data['queryfields']} {$data['query']}";
	$function_req .= "			'crud_{$func}' => '/admin/crud_{$func}.php',\n";
	$li[] = "<li><a href='?choice=none.crud_{$func}'>{$func} - {$title}</li>";
	file_put_contents(INCLUDE_ROOT . '/crud/crud_' . $func . '.php', '<'.'?'."php
function crud_{$func}() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	\$crud = crud::init(\"{$query}\")
		->set_title(\"{$title}\")
		->go();
}
");
file_put_contents(INCLUDE_ROOT . '/crud/cruds.php', '<'.'?'."php
function cruds() {
	add_output('<ul>
".implode("\n",$li)."
</ul>');	
}
");
file_put_contents(INCLUDE_ROOT . '/function_requirements.new', $function_req);
}
