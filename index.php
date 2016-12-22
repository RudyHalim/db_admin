<?php
// error_reporting(0);

include('config.php');
include('class.task.php');



// assign new task for each table data
$task = new Task($config);

// do the looping for the config table list
foreach ($config['table'] as $key => $tabledata) {

	// loading the information first
	$task->prepareTable($tabledata);

	// enable only for localhost testing
	// $task->resetMainTable();

	// create the new master table
	// $task->generateNewMasterTable();
}

