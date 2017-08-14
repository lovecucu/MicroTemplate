<?php

include 'MicroTemplate.class.php';

$template = new MicroTemplate();
$data = array(
	'names'=>array('aaaa', 'bbbb', 'cccc', 'dddd'),
	'name' => 'testjjj'
);
$template->assign($data);
$template->display('index');