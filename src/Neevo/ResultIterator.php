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

namespace Neevo;


/**
 * Result set iterator.
 * @author Martin Srank
 */
class ResultIterator implements \Iterator, \Countable, \SeekableIterator {


	/** @var int */
	private $pointer;

	/** @var Result */
	private $result;

	/** @var Row */
	private $row;


	public function __construct(Result $result){
		$this->result = $result;
	}


	/**
	 * Rewind the iterator.
	 * Force execution for future iterations.
	 * @return void
	 */
	public function rewind(){
		if($this->row !== null)
			$this->result = clone $this->result;
		$this->pointer = 0;
	}


	/**
	 * Move to next row.
	 * @return void
	 */
	public function next(){
		++$this->pointer;
	}


	/**
	 * Check for valid current row.
	 * @return bool
	 */
	public function valid(){
		return ($this->row = $this->result->fetch()) !== false;
	}


	/**
	 * Return the current row.
	 * @return Row
	 */
	public function current(){
		return $this->row;
	}


	/**
	 * Return the key of current row.
	 * @return int
	 */
	public function key(){
		return $this->pointer;
	}


	/**
	 * Implementation of Countable.
	 * @return int
	 * @throws Drivers\DriverException on unbuffered result.
	 */
	public function count(){
		return $this->result->count();
	}


	/**
	 * Implementation of SeekableIterator.
	 * @param int $offset
	 * @throws \OutOfRangeException|Drivers\DriverException
	 */
	public function seek($offset){
		try{
			$this->result->seek($offset);
		} catch(Drivers\DriverException $e){
			throw $e;
		} catch(NeevoException $e){
			throw new \OutOfRangeException("Cannot seek to offset $offset.", null, $e);
		}
		$this->pointer = $offset;
	}


}