<?php
$config = array(
	'database' => array (
		'server' => 'localhost'
		, 'user' => 'root'
		, 'pass' => ''
		, 'dbname' => 'test'
	),
	'server' => array(
		'backup_dump_to_path' => 'newfolder/'
	),
	'table' => array (
		'0' => array(
			'table_name' => 'longtable'
			, 'row_count' => 900000
			, 'datetime_column' => 'created'
			, 'interval_minus_day' => 2
		),
	),
);