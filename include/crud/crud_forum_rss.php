<?php
function crud_forum_rss() {
	require_once(INCLUDE_ROOT . '/rendering/class.crud.php');
	crud::init("select  get_forum_rss")
		->set_title("Latest Forum Posts")
		->go();
}
