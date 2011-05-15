<?php
/**
 * Neevo - Tiny database layer for PHP. (http://neevo.smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * Copyright (c) 2011 Martin Srank (http://smasty.net)
 *
 */


/**
 * Class for data manipulation statements (INSERT, UPDATE, DELETE)
 * @author Martin Srank
 * @package Neevo
 */
class NeevoStmt extends NeevoStmtBase {


	/** @var array */
	protected $values = array();

	/** @var int */
	protected $affectedRows;


	/*  ************  Statement factories  ************  */


	/**
	 * Create UPDATE statement.
	 * @param NeevoConnection $connection
	 * @param string $table
	 * @param array $data
	 * @return NeevoStmt fluent interface
	 */
	public static function createUpdate(NeevoConnection $connection, $table, array $data){
		$obj = new self($connection);
		$obj->type = Neevo::STMT_UPDATE;
		$obj->source = $table;
		$obj->values = $data;
		return $obj;
	}


	/**
	 * Create INSERT statement.
	 * @param NeevoConnection $connection
	 * @param string $table
	 * @param array $values
	 * @return NeevoStmt fluent interface
	 */
	public static function createInsert(NeevoConnection $connection, $table, array $values){
		$obj = new self($connection);
		$obj->type = Neevo::STMT_INSERT;
		$obj->source = $table;
		$obj->values = $values;
		return $obj;
	}


	/**
	 * Create DELETE statement.
	 * @param NeevoConnection $connection
	 * @param string $table
	 * @return NeevoStmt fluent interface
	 */
	public static function createDelete(NeevoConnection $connection, $table){
		$obj = new self($connection);
		$obj->type = Neevo::STMT_DELETE;
		$obj->source = $table;
		return $obj;
	}


	public function run(){
		$result = parent::run();

		try{
			$this->affectedRows = $this->connection->getDriver()->affectedRows();
		} catch(NeevoDriverException $e){
			$this->affectedRows = false;
		}

		return $result;
	}


	/**
	 * Get the ID generated in the last INSERT statement.
	 * @return int|FALSE
	 * @throws NeevoException on non-INSERT statements.
	 */
	public function insertId(){
		if($this->type !== Neevo::STMT_INSERT){
			throw new NeevoException(__METHOD__.' can be called only on INSERT statements.');
		}
		$this->performed || $this->run();
		try{
			return $this->connection->getDriver()->insertId();
		} catch(NeevoImplemenationException $e){
			return false;
		}
	}


	/**
	 * Get the number of rows affected by the statement.
	 * @return int
	 */
	public function affectedRows(){
		$this->performed || $this->run();
		if($this->affectedRows === false){
			throw new NeevoDriverException('Affected rows are not supported by this driver.');
		}
		return $this->affectedRows;
	}


	/**
	 * Get values of statement.
	 * @return array
	 */
	public function getValues(){
		return $this->values;
	}


	/**
	 * Reset state of the statement.
	 * @return void
	 */
	public function resetState(){
		parent::resetState();
		$this->affectedRows = null;
	}


}