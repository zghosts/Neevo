<?php
/**
 * Neevo - Tiny open-source database abstraction layer for PHP
 *
 * Copyright 2010 Martin Srank (http://smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * @author   Martin Srank (http://smasty.net)
 * @license  http://neevo.smasty.net/license  MIT license
 * @link     http://neevo.smasty.net/
 *
 */

/**
 * Neevo SQLite 3 driver (PHP extension 'sqlite3')
 *
 * Driver configuration:
 * - database (or file, db, dbname) => database to select
 * - table_prefix (or prefix) => prefix for table names
 * - charset => Character encoding to set (defaults to utf-8)
 * - dbcharset => Database character encoding (will be converted to 'charset')
 * - resource (instance of SQLite3) => Existing SQLite 3 resource
 *
 * Since SQLite 3 only allows unbuffered queries, number of result rows and seeking
 * is not supported for this driver.
 * 
 * @author Martin Srank
 * @package NeevoDrivers
 */
class NeevoDriverSQLite3 extends NeevoStatementBuilder implements INeevoDriver{

  /** @var Neevo */
  protected $neevo;

  /** @var string */
  private $dbCharset;

  /** @var string */
  private $charset;

  /** @var SQLite3 */
  private $resource;


  /**
   * If driver extension is loaded, sets Neevo reference, otherwise throw exception
   * @param Neevo $neevo
   * @throws NeevoException
   * @return void
   */
  public function  __construct(Neevo $neevo){
    if(!extension_loaded("sqlite3")) throw new NeevoException("PHP extension 'sqlite3' not loaded.");
    $this->neevo = $neevo;
  }


  /**
   * Creates connection to database
   * @param array $config Configuration options
   * @return void
   */
  public function connect(array $config){
    NeevoConnection::alias($config, 'database', 'file');

    if(!isset($config['resource'])) $config['resource'] = null;

    // Connect
    if(!($config['resource'] instanceof SQLite3)){
      try{
        $connection = new SQLite3($config['database']);
      } catch(Exception $e){
          $this->neevo->error($e->getMessage());
      }
    }
    else
      $connection = $config['resource'];

    if(!($connection instanceof SQLite3))
      $this->neevo->error("Opening database file '".$config['database']." failed");
    
    $this->resource = $connection;

    // Set charset
    $this->dbCharset = empty($config['dbcharset']) ? 'UTF-8' : $config['dbcharset'];
    $this->charset = empty($config['charset']) ? 'UTF-8' : $config['charset'];
    if(strcasecmp($this->dbCharset, $this->charset) === 0)
      $this->dbCharset = $this->charset = null;
  }


  /**
   * Closes connection
   * @return void
   */
  public function close(){
    $this->resource->close();
  }


  /**
   * Frees memory used by result
   *
   * NeevoResult automatically NULLs the resource, so this is not necessary.
   * @param SQLite3Result $resultSet
   * @return bool
   */
  public function free($resultSet){
    return true;
  }


  /**
   * Executes given SQL statement
   * @param string $queryString Query-string.
   * @return SQLite3Result|bool
   */
  public function query($queryString){

    if($this->dbCharset !== null)
      $queryString = iconv($this->charset, $this->dbCharset . '//IGNORE', $queryString);

    return @$this->resource->query($queryString);
  }


  /**
   * Error message with driver-specific additions
   * @param string $message Error message
   * @return array Format: array($error_message, $error_number)
   */
  public function error($message){
    $no = $this->resource->lastErrorCode();
    $msg = $message. '. ' . $this->resource->lastErrorMsg();
    return array($msg, $no);
  }


  /**
   * Fetches row from given result set as associative array.
   * @param SQLite3Result $resultSet Result set
   * @return array
   */
  public function fetch($resultSet){
    $row = $resultSet->fetchArray(SQLITE3_ASSOC);
    $charset = $this->charset === null ? null : $this->charset.'//TRANSLIT';

    if($row && $charset){
      $fields = array();
      foreach($row as $key=>$val){
        if($charset !== null && is_string($val))
          $val = iconv($this->dbcharset, $charset, $val);
        $fields[str_replace(array('[', ']'), '', $key)] = $val;
      }
      return $fileds;
    }
    return $row;
  }


  /**
   * Fetches all rows from given result set as associative arrays.
   * @param SQLite3Result $resultSet Result set
   * @return array
   */
  public function fetchAll($resultSet){
    throw new NotImplementedException();
  }


  /**
   * Move internal result pointer
   *
   * Not supported because of unbuffered queries.
   * @param SQLite3Result $resultSet
   * @param int $offset
   * @return bool
   * @throws NotSupportedException
   */
  public function seek($resultSet, $offset){
    throw new NotSupportedException('Not supported on unbuffered queries.');
  }


  /**
   * Get the ID generated in the INSERT statement
   * @return int
   */
  public function insertId(){
    return $this->resource->lastInsertRowID();
  }


  /**
   * Randomize result order.
   * @param NeevoResult $statement NeevoResult instance
   * @return NeevoResult
   */
  public function rand(NeevoResult $statement){
    $statement->order('RANDOM()');
  }


  /**
   * Number of rows in result set.
   *
   * Not supported because of unbuffered queries.
   * @param SQLite3Result $resultSet
   * @return int|FALSE
   * @throws NotSupportedException
   */
  public function rows($resultSet){
    throw new NotSupportedException('Not supported on unbuffered queries.');
  }


  /**
   * Number of affected rows in previous operation.
   * @return int
   */
  public function affectedRows(){
    return $this->resource->changes();
  }


  /**
   * Builds statement from NeevoResult instance
   * @param NeevoResult $statement NeevoResult instance
   * @return string the statement
   */
  public function build(NeevoResult $statement){

    $where = '';
    $order = '';
    $group = '';
    $limit = '';
    $q = '';

    $table = $statement->getTable();

    if($statement->getConditions())
      $where = $this->buildWhere($statement);

    if($statement->getOrdering())
      $order = $this->buildOrdering($statement);

    if($statement->getGrouping())
      $group = $this->buildGrouping($statement);

    if($statement->getLimit()) $limit = " LIMIT " .$statement->getLimit();
    if($statement->getOffset()) $limit .= " OFFSET " .$statement->getOffset();

    if($statement->getType() == Neevo::STMT_SELECT){
      $cols = $this->buildSelectCols($statement);
      $q .= "SELECT $cols FROM $table$where$group$order$limit";
    }

    elseif($statement->getType() == Neevo::STMT_INSERT && $statement->getValues()){
      $insert_data = $this->buildInsertData($statement);
      $q .= "INSERT INTO $table$insert_data";
    }

    elseif($statement->getType() == Neevo::STMT_UPDATE && $statement->getValues()){
      $update_data = $this->buildUpdateData($statement);
      $q .= "UPDATE $table$update_data$where";
    }

    elseif($statement->getType() == Neevo::STMT_DELETE)
      $q .= "DELETE FROM $table$where";

    return $q.';';
  }


  /**
   * Escapes given value
   * @param mixed $value
   * @param int $type Type of value (Neevo::TEXT, Neevo::BOOL...)
   * @return mixed
   */
  public function escape($value, $type){
    switch($type){
      case Neevo::BOOL:
        return $value ? 1 :0;

      case Neevo::TEXT:
        return "'". $this->resource->escapeString($value) ."'";
      
      case Neevo::DATETIME:
        return ($value instanceof DateTime) ? $value->format("'Y-m-d H:i:s'") : date("'Y-m-d H:i:s'", $value);
        
      default:
        $this->neevo->error('Unsupported data type');
        break;
    }
  }

}