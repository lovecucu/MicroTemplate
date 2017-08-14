<?php

include 'MicroTemplate.class.php';

$template = new MicroTemplate();
$template->config('caching', true);
$data = array(
	'names'=>array('aaaa', 'bbbb', 'cccc', 'dddd'),
	'name' => 'test'
);
$template->assign($data);
$template->display('index');

$template->clear_cache('index');
$template->clear_all_caches();