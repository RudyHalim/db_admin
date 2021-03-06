<?php
class Task
{
	private $db;
	private $tabledata;
	private $config;

	private $master_table_name;
	private $new_master_table_name;
	private $dump_file_name;

	private $temp_file = 'temp_auto.json';

	function __construct($config)
	{
		$this->config = $config;

		// trying to connect to db
		$this->connectDb();
	}

	function connectDb()
	{
		// connect to the database
		$db = new mysqli($this->config['database']['server']
					, $this->config['database']['user']
					, $this->config['database']['pass']
					, $this->config['database']['dbname']);

		if ($db->connect_errno > 0) {
			die('Unable to connect to database ['.$db->connect_error.']');
		}

		// successful connect db
		$this->db = $db;
	}

	function isValidTempFile()
	{
		return (file_exists($this->temp_file) && filesize($this->temp_file) > 2);
	}

	function prepareTable($tabledata)
	{
		// read from temp json file if file exists
		if($this->isValidTempFile()) {
			$tabledata = json_decode(file_get_contents($this->temp_file), true);
		} 

		// assign the parameter
		$this->tabledata 				= $tabledata;
		$this->master_table_name 		= $this->tabledata['table_name'];
		$this->new_master_table_name 	= $this->tabledata['table_name'].date("_Y_m_d_").(microtime()*1000000);
		$this->dump_file_name 			= $this->new_master_table_name.".sql";

		// write the temp file if file not exists
		if(!$this->isValidTempFile()) {
			$create_file = file_put_contents($this->temp_file, json_encode(get_object_vars($this)) ) or die("Failed to write temp file.");
			echo "Created temporary parameter file ".$this->temp_file." with ".filesize($this->temp_file)." bytes.".PHP_EOL;
		}
	}

	function resetMainTable()
	{
		// first we need to drop the existing table for testing purpose
		$this->doDeleteTable($this->tabledata['table_name']);

		// create again the table structure with new indexing key
		$this->createMainTable();

		// insert the long and many records to be cut later
		$this->addBulkRows();
	}

	function doDeleteTable($tablename)
	{
		$q = "DROP TABLE IF EXISTS ".$tablename.";";
		$sql = $this->db->query($q) or die($this->db->error);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function createMainTable()
	{
		$q = "
			CREATE TABLE IF NOT EXISTS ".$this->tabledata['table_name']." (
				id INT(6) AUTO_INCREMENT PRIMARY KEY,
				title VARCHAR(5),
				created DATETIME
			)engine=myisam;
		";
		$sql = $this->db->query($q) or die($this->db->error);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function addBulkRows()
	{
		for ($i=1; $i <= $this->tabledata['row_count']; $i++) { 
			$q = "INSERT INTO ".$this->tabledata['table_name']." SET 
					title = '".$this->randomString(5)."' 
					, created = NOW() - INTERVAL ".($this->tabledata['row_count'] - $i)." MINUTE
				";
			$sql = $this->db->query($q) or die($this->db->error);
			echo $this->singleline($q)." - OK".PHP_EOL;
		}
	}

	function singleline($string)
	{
		return str_replace(array("\r", "\n", "\t"), "", $string);
	}

	function randomString($length)
	{
		$characters = 'abcdefghijklmnopqrstuvwxyz';
	    $charactersLength = strlen($characters);
	    $randomString = '';
	    for ($i = 0; $i < $length; $i++) {
	        $randomString .= $characters[rand(0, $charactersLength - 1)];
	    }
	    return $randomString;
	}

	function generateNewMasterTable() 
	{
		// start with rename master table to backup table
		$this->renameMasterTable();

		// then create the new master table from the backup table structure
		$this->createNewMasterTable();

		// insert record H - 2 day to the new master table
		$this->insertHDaysToNewMasterTable();

		// dump the backup table using mysqldump
		$this->doDumpTable();

		// transfer the dump file to the backup server
		$this->moveDumpToBackupServer();

		// import the dump files to backup database server

		// tidy up the tables to monthly backup tables
	}

	function renameMasterTable()
	{
		$q = "RENAME TABLE $this->master_table_name TO $this->new_master_table_name;";
		$sql = $this->db->query($q);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function createNewMasterTable()
	{
		$q = "CREATE TABLE IF NOT EXISTS $this->master_table_name LIKE $this->new_master_table_name;";
		$sql = $this->db->query($q) or die($this->db->error);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function insertHDaysToNewMasterTable()
	{
		$q = "INSERT IGNORE INTO $this->master_table_name 
				SELECT * FROM $this->new_master_table_name 
					WHERE DATE(".$this->tabledata['datetime_column'].") > DATE(NOW()) - INTERVAL ".$this->tabledata['interval_minus_day']." DAY ;";
		$sql = $this->db->query($q) or die($this->db->error);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function doDumpTable()
	{
		$command = "mysqldump -u ".$this->config['database']['user']." -p".$this->config['database']['pass']." ".$this->config['database']['dbname']." ".$this->new_master_table_name." > ".$this->dump_file_name;
		system($command, $return);
		$status = $return == '0' ? true : false;

		echo $this->singleline($command)." - ".($status ? "OK" : "Return Code Error: ".$return).PHP_EOL;

		// if successfully dump table, and file size bigger than zero, then delete the old table
		if($status &&  filesize($this->dump_file_name) > 0) {
			$this->doDeleteTable($this->new_master_table_name);
		} else {
			echo "Either dump process is fail or dump file is not bigger than zero bytes.".PHP_EOL;
		}
	}

	function moveDumpToBackupServer()
	{
		if(file_exists($this->dump_file_name)) {
			$oldname = $this->dump_file_name;
			$newname = $this->config['server']['backup_dump_to_path'].$this->dump_file_name;

			$move = rename($oldname, $newname);
			echo "Move $oldname to $newname - ".$move.PHP_EOL;
		}
	}
}
