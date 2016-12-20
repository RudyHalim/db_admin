<?php
class Task
{
	private $db;
	private $table_name;
	private $row_count;

	function __construct($db, $tabledata)
	{
		$this->db 			= $db;
		$this->table_name 	= $tabledata['table_name'];
		$this->row_count 	= $tabledata['row_count'];
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
		$q = "DROP TABLE IF EXISTS $this->table_name;";
		$sql = $this->db->query($q) or die($this->db->error);
		
		echo $this->singleline($q)." - OK".PHP_EOL;
	}

	function createMainTable()
	{
		$q = "
			CREATE TABLE IF NOT EXISTS $this->table_name (
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
		for ($i=1; $i <= $this->row_count; $i++) { 
			$q = "INSERT INTO $this->table_name SET 
					title = '".$this->randomString(5)."' 
					, created = NOW() - INTERVAL ".($this->row_count - $i)." MINUTE
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

		// then create the new master table from the backup table structure

		// insert record H - 2 day to the new master table

		// dump the backup table using mysqldump

		// transfer the dump file to the backup server

		// import the dump files to backup database server

		// tidy up the tables to monthly backup tables
	}
}
