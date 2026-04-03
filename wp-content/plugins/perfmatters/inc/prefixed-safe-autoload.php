<?php
declare(strict_types=1);

$safe_root = dirname(__DIR__) . '/vendor-prefixed/thecodingmachine/safe';

if(!is_dir($safe_root)) {
	return;
}

$special_cases = $safe_root . '/lib/special_cases.php';
if(file_exists($special_cases)) {
	require_once $special_cases;
}

$generated_dir = $safe_root . '/generated';
if(!is_dir($generated_dir)) {
	return;
}

$generated_files = glob($generated_dir . '/*.php');
if($generated_files === false) {
	return;
}

foreach($generated_files as $generated_file) {
	$basename = basename($generated_file);
	if($basename === 'functionsList.php' || $basename === 'rector-migrate.php') {
		continue;
	}
	require_once $generated_file;
}
