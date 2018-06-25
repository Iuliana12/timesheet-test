<?php
//This requires PEAR and the MDB2 package to be installed and functional 
require_once 'MDB2.php';
require_once 'config.php';
class DB{
	private $dsn = Array(
	    'phptype'  => DB_PHPTYPE,
		'protocol' => DB_PROTOCOL,
		'database' => DB_DATABASE,
	    'username' => DB_USERNAME,
	    'password' => DB_PASSWORD,
	    'hostspec' => DB_HOST,
		'port'     => DB_PORT,
	    'new_link' => false
	);
	private $options = Array(
		'result_buffering' => true,
		'persistent' => false,
		'use_transactions' => true,
		'debug' => DB_DEBUG, 
		'debug_handler' => 'DB::debug'
	);
	private $database = null;//the database connection
	private $result = null;//the result of an sql query or exec
	private $index = -1;//the row index in the result
	private $rowArray = null;//the last read row from the result
	private $extended;
	private $sqlStatement;
	
	public $function;
		
	function __construct($dsn = null){
		if($dsn==null){
			$this->database = MDB2::singleton($this->dsn, $this->options);
		}else{
			$this->database = MDB2::singleton($dsn, $this->options);
		}
		if(!$this->isDbOK()){
			return null;
		}
		$this->database->loadModule('Function');
		if(PEAR::isError ($this->database->function)){
			die('Function MDB2 package could not be loaded');
		}
		$this->function = $this->database->function;
		$this->database->loadModule('Extended', $this->extended);
		if(PEAR::isError ($this->database->extended)){
			die('Extended MDB2 package could not be loaded');
		}
		$this->extended = $this->database->extended;
		$this->database->connect();
		$this->database->setFetchMode(MDB2_FETCHMODE_ASSOC);
	}
	function __destruct(){
		if($this->isResultOK()){
			$this->result->free();
		}
		/**
		 * you must not disconnect from the database when using a singleton because some flags will be
		 * changed to their default values (e.g in_transaction = false) and other variables that use
		 * the same connection will be affected (e.g lose a transaction)
		 */
//		if($this->isDbOK()){
//			$this->database->disconnect();
//		}
	}
	function connect(){
		if(!$this->isDbOK()){
			return null;
		}
		$this->database->connect();
	}
	function disconnect(){
		
		$this->database->disconnect();
	}
	function query($sqlStatement, $types = null){
		$this->sqlStatement = $sqlStatement; 
		if(!$this->isDbOK()){
			return null;
		}
		$this->result = $this->database->query($sqlStatement, $types);
		if($this->isResultOK()){
			$this->rowArray = $this->result->fetchRow();
			$this->index = 0;
		}
	}
	function queryMultiple($sqlStatement, $types = null){
		$this->sqlStatement = $sqlStatement; 
		if(!$this->isDbOK()){
			return null;
		}
		$multi_query = $this->database->setOption('multi_query', true);
		if(!PEAR::isError($multi_query)){
			$this->result = $this->database->query($sqlStatement, $types);
		}
		else {
			//emulating multi query
			$queries = explode(";",$sqlStatement);
			foreach ($queries as $sql){
				$this->result = $this->database->query($sql);
			}
		}
		if($this->isResultOK()){
			$this->rowArray = $this->result->fetchRow();
			$this->index = 0;
		}
	}
	function exec($sqlStatement){
		$this->sqlStatement = $sqlStatement;
		if(!$this->isDbOK()){
			return null;
		}
		$this->result = $this->database->exec($sqlStatement);
		$this->isResultOK();
	}
	function execMultiple($sqlStatement){
		$this->sqlStatement = $sqlStatement;
		if(!$this->isDbOK()){
			return null;
		}
		$multi_query = $this->database->setOption('multi_query', true);
		if(!PEAR::isError($multi_query)){
			$this->result = $this->database->exec($sqlStatement);
		}
		else {
			//emulating multi query
			$queries = explode(";",$sqlStatement);
			foreach ($queries as $sql){
				$this->result = $this->database->exec($sql);
			}
		}
		$this->isResultOK();
	}
	function autoExec($tableName, $fieldsValues, $mode = MDB2_AUTOQUERY_INSERT, $where = false, $types = null ){
		$this->sqlStatement = 'autoexec on '.$tableName;
		if(!$this->isDbOK()){
			return null;
		}
		$this->result = $this->extended->autoExecute($tableName, $fieldsValues, $mode, $where, $types);
		$this->isResultOK();
	}
	function numRows(){
		if(!$this->isResultOK()){
			return 0;
		}
		return $this->result->numRows();
	}
	function seek($x){
		if(!$this->isResultOK()){
			return false;
		}
		if($x<0 || $x>= $this->numRows()){
			return false;
		}
		$this->index = $x;
		$this->result->seek($x);
		$this->rowArray = $this->result->fetchRow(); 
		return true;
	}
	function nextRow(){
		return $this->seek($this->index + 1);
	}
	function getRow(){
		if(!$this->isResultOK()){
			return Array();
		}
		if($this->rowArray == null){
			$this->nextRow();
		}
		return $this->rowArray;
	}
	function getElement($element){
		if($this->rowArray !== null && array_key_exists($element,$this->rowArray))
			return $this->rowArray[$element];
		return null;
	}
	function getAffectedRows() {
		if(!$this->result){
			return 0;
		}
		if(is_numeric($this->result)){//after efecuting a manipulating sql statement with exec, the result will be the number of affcted rows
			return $this->result;
		}
	}
	function getColumnNames(){
		if($this->isResultOK()){
			return $this->result->getColumnNames();
		}
	}
	function getLastInsertID($table, $IDfiled){
		if(!$this->isDbOK()){
			return null;
		}
		return $this->database->lastInsertID($table, $IDfiled);
	}
	function beginTransaction($savepoint = null){
		if(!$this->isDbOK()){
			return null;
		}
		if (!$this->database->supports('transactions')) {
   			 return null;
		}
		if($savepoint !== null && !$this->database->supports('savepoints')){
			$savepoint = null;
		}
		$this->result = $this->database->beginTransaction($savepoint);
		return $this->isResultOK();
	}
	function commit($savepoint = null){
		if(!$this->isDbOK()){
			return null;
		}
		if (!$this->database->supports('transactions')) {
   			 return null;
		}
		$this->result = $this->database->commit($savepoint);
		return $this->isResultOK();
	}
	function rollback($savepoint = null){
		if(!$this->isDbOK()){
			return null;
		}
		if (!$this->database->supports('transactions')) {
   			 return null;
		}
		$this->result = $this->database->rollback($savepoint);
		return $this->isResultOK();
	}
	function setResultTypes($types){
		if($this->isResultOK()){
			$this->result->setResultTypes($types);
		}
	}
	function setLimit($rows, $offset){
		if(!$this->isDbOK()){
			return null;
		}
		$this->database->setLimit($rows, $offset);
	}
	function free(){
		if($this->isResultOK()){
			$this->result->free();
		}
	}
	function quote($string){
		if(!$this->isDbOK()){
			return '';
		}
		$str = $this->database->quote($string);
		if(PEAR::isError($str)){
			$this->onError($str);
		}
		return $str;
	}
	function escape($string){
		if(!$this->isDbOK()){
			return '';
		}
		$str = $this->database->escape($string);
		if(PEAR::isError($str)){
			$this->onError($str);
		}
		return $str;
	}
	
	function isDbOK(){
		if (PEAR::isError($this->database)) {
			$this->onError($this->database);
			return false;
		}
		if (is_object($this->database)) {
			return true;
		}
		return false;
	}
	function isResultOK(){
		if (PEAR::isError($this->result)) {
			$this->onError($this->result);
			return false;
		}
		if (is_object($this->result)) {
			return true;
		}
		return false;
	}
	
	function onError($error){
		if (PEAR::isError($error)) {
			if($this->options["debug"] > 0) {
				echo $error->getMessage()."<br/>";
				echo $error->getUserInfo()."<br/>";
			}
			if($this->options["debug"] >= 2) {
				echo $this->sqlStatement."<br/>";
			}
			die('a DB error has occured, please contact your web developer <br/>'); 
		}
		else print_r($error);
	}
	public static function debug($stuff){
		echo $stuff."<br/>";
	}
}
?>