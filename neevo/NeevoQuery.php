<?php
/**
 * Neevo - Tiny open-source database abstraction layer for PHP
 *
 * Copyright 2010 Martin Srank (http://smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * @copyright  Copyright (c) 2010 Martin Srank (http://smasty.net)
 * @license    http://www.opensource.org/licenses/mit-license.php  MIT license
 * @link       http://labs.smasty.net/neevo/
 * @package    Neevo
 * @version    0.02dev
 *
 */

/**
 * Neevo class for SQL query abstraction
 * @package Neevo
 */
class NeevoQuery {

  public $table, $type, $limit, $offset, $neevo, $resource, $time, $sql;
  public $where, $order, $columns, $data = array();


  /**
   * Query base constructor
   * @param array $object Reference to instance of Neevo class which initialized Query
   * @param string $type Query type. Possible values: select, insert, update, delete
   * @param string $table Table to interact with
   */
  function  __construct(Neevo $object, $type = '', $table = ''){
    $this->neevo = $object;

    $this->type($type);
    $this->table($table);
  }


  /**
   * Sets table to interact
   * @param string $table
   * @return NeevoQuery
   */
  public function table($table){
    $this->table = $table;
    return $this;
  }


  /**
   * Sets query type. Possibe values: select, insert, update, delete
   * @param string $type
   * @return NeevoQuery
   */
  public function type($type){
    $this->type = $type;
    return $this;
  }


  /**
   * Method for running direct SQL code
   * @param string $sql Direct SQL code
   * @return NeevoQuery
   */
  public function sql($sql){
    $this->sql = $sql;
    return $this;
  }


  /**
   * Sets columns to retrive in SELECT queries
   * @param mixed $columns Array or comma-separated list of columns.
   * @return NeevoQuery
   */
  public function cols($columns){
    if(!is_array($columns)) $columns = explode(',', $columns);
    $this->columns = $columns;
    return $this;
  }


  /**
   * Data for INSERT and UPDATE queries
   * @param array $data Data in format "$column=>$value"
   * @return NeevoQuery
   */
  public function data(array $data){
    $this->data = $data;
    return $this;
  }


  /**
   * Sets WHERE condition for Query
   *
   * <p>Supports LIKE and IN functions</p>
   *
   * @param string $where Column to use and optionaly operator/function: "email !=", "email LIKE" or "email IN".
   * @param mixed $value Value to search for: "spam@foo.com", "%@foo.com" or array('john@foo.com', 'doe@foo.com', 'john.doe@foo.com')
   * @param string $glue Glue (AND, OR, etc.) to use betweet this and next WHERE condition. If not set, AND will be used.
   * @return NeevoQuery
   */
  public function where($where, $value, $glue = null){
    $where_condition = explode(' ', $where);
    if(is_null($value)){
      $where_condition[1] = "IS";
      $value = "NULL";
    }
    if(is_array($value)) $where_condition[1] = "IN";
    if(!isset($where_condition[1])) $where_condition[1] = '=';
    $column = $where_condition[0];
    $condition = array($column, $where_condition[1], $value, strtoupper($glue));
    $this->where[] = $condition;
    return $this;
  }


  /**
   * Sets ORDER BY rule for Query
   * @param string $args [Infinite arguments] Order rules: "col_name ASC", "col_name" or "col_name DESC", etc...
   * @return NeevoQuery
   */
  public function order($args){
    $rules = array();
    $arguments = func_get_args();
    foreach ($arguments as $argument) {
      $order_rule = explode(' ', $argument);
      $rules[] = $order_rule;
    }
    $this->order = $rules;
    return $this;
  }


  /**
   * Sets limit (and offset) for Query
   * @param int $limit Limit
   * @param int $offset Offset
   * @return NeevoQuery
   */
  public function limit($limit, $offset = null){
    $this->limit = $limit;
    if(isset($offset) && $this->type == 'select') $this->offset = $offset;
    return $this;
  }


  /**
   * Prints consequential Query (highlighted by default)
   * @param bool $color Highlight query or not (default: yes)
   * @param bool $return_string Return the string or not (default: no)
   * @return NeevoQuery
   */
  public function dump($color = true, $return_string = false){
    $code = $color ? NeevoStatic::highlight_sql($this->build()) : $this->build();
    if(!$return_string) echo $code;
    return $return_string ? $code : $this;
  }


  /**
   * Performs Query
   * @param bool $catch_error Catch exception by default if mode is not E_STRICT
   * @return resource
   */
  public function run($catch_error = false){
    $start = explode(" ", microtime());
    $query = $this->neevo->query($this, $catch_error);
    $end = explode(" ", microtime());
    $time = round(max(0, $end[0] - $start[0] + $end[1] - $start[1]), 4);
    $this->time($time);
    $this->resource = $query;
    return $query;
  }


  /**
   * Fetches data from given Query resource and executes query (if it haven't already been executed)
   * @return mixed Array or string (if only one value is returned) or FALSE (if nothing is returned).
   */
  public function fetch(){
    $resource = is_resource($this->resource) ? $this->resource : $this->run();
    $rows = $this->neevo->fetch($resource);
    return $resource ? $rows : $this->neevo->error("Fetching result data failed");
  }


  /**
   * Move internal result pointer
   * @param int $row_number Row number of the new result pointer.
   * @return bool
   */
  public function seek($row_number){
    if(!is_resource($this->resource)) $this->run();

    $seek = $this->neevo->driver()->seek($this->resource, $row_number);
    return $seek ? $seek : $this->neevo->error("Cannot seek to row $row_number");
  }


  /**
   * Randomize result order. (Shorthand for NeevoQuery->order('RAND()');)
   * @return NeevoQuery
   */
  public function rand(){
    $this->neevo->driver()->rand($this);
    return $this;
  }


  /**
   * Returns number of affected rows for INSERT/UPDATE/DELETE queries and number of rows in result for SELECT queries
   * @return mixed Number of rows (int) or FALSE
   */
  public function rows(){
    return $this->neevo->driver()->rows($this, $string);
  }


  /**
   * Sets and/or returns Execution time of Query
   * @param int $time Time value to set.
   * @return int Query execution time
   */
  public function time($time = null){
    if(isset($time)) $this->time = $time;
    return $this->time;
  }


  /**
   * Returns some info about this Query as an array
   * @return array Info about Query
   */
  public function info(){
    $exec_time = $this->time() ? $this->time() : -1;
    $rows = $this->time() ? $this->rows() : -1;
    $info = array(
      'resource' => $this->neevo->resource(),
      'query' => $this->dump($html, true),
      'exec_time' => $exec_time,
      'rows' => $rows
    );
    if($this->type == 'select') $info['query_resource'] = $this->resource;
    return $info;
  }


  /**
   * Unsets defined parts of Query (WHERE conditions, ORDER BY clauses, affected columns (INSERT, UPDATE), LIMIT, etc.).
   *
   * <p>To unset 2nd WHERE condition from Query: <code>SELECT * FROM table WHERE id=5 OR name='John Doe' OR ...</code> use following: <code>$select->undo('where', 2);</code></p>
   * <p>To unset 'name' column from Query: <code>UPDATE table SET name='John Doe', id=4 WHERE ...</code> use following: <code>$update->undo('value', 'name');</code></p>
   *
   * @param string $sql_part Part of Query to unset. Possible values are: (string)
   * <ul>
     * <li>where (for WHERE conditions)</li>
     * <li>order (for ORDER BY clauses)</li>
     * <li>column (for selected columns in SELECT queries)</li>
     * <li>value (for values to put/set in INSERT and UPDATE)</li>
     * <li>limit (for LIMIT clause)</li>
     * <li>offset (for OFFSET clause)</li>
   * </ul>
   * @param mixed $position Exact piece of Query part. This can be:
   * <ul>
     * <li>(int) Ordinal number of Query part piece (WHERE condition, ORDER BY clause, columns in SELECT queries) to unset.</li>
     * <li>(string) Column name from defined values (values to put/set in INSERT and UPDATE queries) to unset.</li>
     * <li>(array) Array of options (from pevious two) if you want to unset more than one piece of Query part (e.g 2nd and 3rd WHERE condition).</li>
   * </ul>
   * This argument is not required for LIMIT & OFFSET. Default is (int) 1.
   * @return NeevoQuery
   */
  public function undo($sql_part, $position = 1){
    switch (strtolower($sql_part)) {
      case 'where':
        $part = 'where';
        break;
      case 'order';
        $part = 'order';
        break;
      case 'column';
        $part = 'columns';
        break;
      case 'value';
        $part = 'data';
        break;
      case 'limit':
        $part = 'limit';
        $str = true;
        break;
      case 'offset':
        $part = 'offset';
        $str = true;
        break;
      default:
        $this->neevo->error("Undo failed: No such Query part '$sql_part' supported for undo()", true);
        break;
    }

    if($str)
      unset($this->$part);
    else{
      if(isset($this->$part)){
        $positions = array();
        if(!is_array($position)) $positions[] = $position;
        foreach ($positions as $pos) {
          $pos = is_numeric($pos) ? $pos-1 : $pos;
          $apart = $this->$part;
          unset($apart[$pos]);
          foreach($apart as $key=>$value){
            $loop[$key] = $value;
          }
          $this->$part = $loop;
        }
      } else $this->neevo->error("Undo failed: No such Query part '$sql_part' for this kind of Query", true);
    }
    return $this;
  }


  /**
   * Builds Query from NeevoQuery instance
   * @return string the Query
   */
  public function build(){

    return $this->neevo->driver()->build($this);

  }

}
?>