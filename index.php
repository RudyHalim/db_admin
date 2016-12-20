<?php
// error_reporting(0);

include('config.php');
include('class.task.php');

// connect to the database
$db = new mysqli($config['database']['server']
		, $config['database']['user']
		, $config['database']['pass']
		, $config['database']['dbname']);

if ($db->connect_errno > 0) {
	die('Unable to connect to database ['.$db->connect_error.']');
}


// do the looping for the config table list
foreach ($config['table'] as $key => $tabledata) {

	// assign new task for each table data
	$task = new Task($db, $tabledata);

	// clean and re-insert the situation
	$task->resetMainTable();

	// create the new master table
	$task->generateNewMasterTable();
}

