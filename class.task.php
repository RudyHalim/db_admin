<?php
class Task
{
	private $db;
	private $tabledata;

	private $master_table_name;
	private $new_master_table_name;

	function __construct($db, $tabledata)
	{
		$this->db 						= $db;
		$this->tabledata 				= $tabledata;

		$this->master_table_name 		= $this->tabledata['table_name'];
		$this->new_master_table_name 	= $this->tabledata['table_name'].date("_Y_m_d_").(microtime()*1000000);
	}

	function resetMainTable()
	{
		// first we need to drop the existing table for testing purpose
		$this->dropMainTable();

		// create again the table structure with new indexing key
		$this->createMainTable();

		// insert the long and many records to be cut later
		$this->addBulkRows();
	}

	function dropMainTable()
	{
		$q = "DROP TABLE IF EXISTS ".$this->tabledata['table_name'].";";
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

		// transfer the dump file to the backup server

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
}
