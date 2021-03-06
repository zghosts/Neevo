<?php
/**
 * Neevo - Tiny database layer for PHP. (http://neevo.smasty.net)
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file license.txt.
 *
 * Copyright (c) 2013 Smasty (http://smasty.net)
 *
 */

namespace Neevo;

use BadMethodCallException;
use InvalidArgumentException;
use RuntimeException;
use SplObjectStorage;
use Traversable;


/**
 * Neevo statement abstract base ancestor.
 *
 * @method BaseStatement and($expr, $value = true)
 * @method BaseStatement or($expr, $value = true)
 * @method BaseStatement if($condition)
 * @method BaseStatement else()
 * @method BaseStatement end()
 *
 * @author Smasty
 */
abstract class BaseStatement implements ObservableInterface {


	/** @var string */
	protected $source;

	/** @var string */
	protected $type;

	/** @var int */
	protected $limit;

	/** @var int */
	protected $offset;

	/** @var array */
	protected $conditions = array();

	/** @var array */
	protected $sorting = array();

	/** @var float */
	protected $time;

	/** @var bool */
	protected $performed;

	/** @var Connection */
	protected $connection;

	/** @var array Event type conversion table */
	protected static $eventTable = array(
		Manager::STMT_SELECT => ObserverInterface::SELECT,
		Manager::STMT_INSERT => ObserverInterface::INSERT,
		Manager::STMT_UPDATE => ObserverInterface::UPDATE,
		Manager::STMT_DELETE => ObserverInterface::DELETE
	);

	/** @var array */
	protected $subqueries = array();

	/** @var SplObjectStorage */
	protected $observers;

	/** @var array */
	private $stmtConditions = array();


	/**
	 * Create statement.
	 * @param Connection $connection
	 */
	public function __construct(Connection $connection){
		$this->connection = $connection;
		$this->observers = new SplObjectStorage;
	}


	/**
	 * String representation of object.
	 * @return string
	 */
	public function __toString(){
		return (string) $this->parse();
	}


	/**
	 * Create clone of object.
	 */
	public function __clone(){
		$this->resetState();
	}


	/**
	 * @return BaseStatement fluent interface
	 * @internal
	 * @throws BadMethodCallException
	 * @throws InvalidArgumentException
	 */
	public function __call($name, $args){
		$name = strtolower($name);

		// AND/OR where() glues
		if(in_array($name, array('and', 'or'))){
			if($this->validateConditions())
				return $this;

			$this->resetState();
			if(($count = count($this->conditions)) !== 0)
				$this->conditions[$count - 1]['glue'] = strtoupper($name);
			if(count($args) >= 1)
				call_user_func_array(array($this, 'where'), $args);
			return $this;
		}

		// Conditional statements
		elseif(in_array($name, array('if', 'else', 'end'))){

			// Parameter counts
			if(count($args) < 1 && $name == 'if')
				throw new InvalidArgumentException('Missing argument 1 for ' . __CLASS__ . "::$name().");

			$conds = & $this->stmtConditions;
			if($name == 'if')
				$conds[] = (bool) $args[0];
			elseif($name == 'else')
				$conds[count($conds) - 1] = !end($conds);
			elseif($name == 'end')
				array_pop($conds);

			return $this;
		}
		throw new BadMethodCallException('Call to undefined method ' . __CLASS__ . "::$name()");
	}


	/**
	 * Sets WHERE condition. Accepts infinite arguments.
	 *
	 * More calls append conditions with 'AND' operator. Conditions can also be specified
	 * by calling and() / or() methods the same way as where().
	 * Corresponding operator will be used.
	 *
	 * Accepts associative array in field => value form.
	 * @param string|array|Traversable $expr
	 * @param mixed $value
	 * @return BaseStatement fluent interface
	 */
	public function where($expr, $value = true){
		if((is_array($expr) || $expr instanceof Traversable) && $value === true){
			foreach($expr as $key => $val) $this->where($key, $val);
			return $this;
		}

		if($this->validateConditions())
			return $this;

		$this->resetState();

		// Simple format
		if(strpos($expr, '%') === false){
			$field = trim($expr);
			$this->conditions[] = array(
				'simple' => true,
				'field' => $field,
				'value' => $value,
				'glue' => 'AND'
			);
			if($value instanceof self)
				$this->subqueries[] = $value;
			return $this;
		}

		// Format with modifiers
		$args = func_get_args();
		array_shift($args);
		preg_match_all('~%(bin|sub|b|i|f|s|d|a|l)?~i', $expr, $matches);
		$this->conditions[] = array(
			'simple' => false,
			'expr' => $expr,
			'modifiers' => $matches[0],
			'types' => $matches[1],
			'values' => $args,
			'glue' => 'AND'
		);
		foreach($args as $arg){
			if($arg instanceof self)
				$this->subqueries[] = $arg;
		}
		return $this;
	}


	/**
	 * Defines order. More calls append rules.
	 * @param string|array|Traversable $rule
	 * @param string $order Use constants - Manager::ASC, Manager::DESC
	 * @return BaseStatement fluent interface
	 */
	public function order($rule, $order = null){
		if($this->validateConditions())
			return $this;

		$this->resetState();

		if(is_array($rule) || $rule instanceof Traversable){
			foreach($rule as $key => $val){
				$this->order($key, $val);
			}
			return $this;
		}
		$this->sorting[] = array($rule, $order);

		return $this;
	}


	/**
	 * Sets LIMIT and OFFSET clauses.
	 * @param int $limit
	 * @param int $offset
	 * @return BaseStatement fluent interface
	 */
	public function limit($limit, $offset = null){
		if($this->validateConditions())
			return $this;

		$this->resetState();
		$this->limit = array($limit,
			($offset !== null && $this->type === Manager::STMT_SELECT) ? $offset : null);
		return $this;
	}


	/**
	 * Randomizes order. Removes any other order clause.
	 * @return BaseStatement fluent interface
	 */
	public function rand(){
		if($this->validateConditions())
			return $this;

		$this->resetState();
		$this->connection->getDriver()->randomizeOrder($this);
		return $this;
	}


	/**
	 * Prints out syntax highlighted statement.
	 * @param bool $return
	 * @return string|BaseStatement fluent interface
	 */
	public function dump($return = false){
		$sql = PHP_SAPI === 'cli'
			? preg_replace('/\s+/', ' ', $this->parse()) . "\n"
			: Manager::highlightSql($this->parse());
		if(!$return)
			echo $sql;
		return $return ? $sql : $this;
	}


	/**
	 * Performs the statement.
	 * @return resource|bool
	 */
	public function run(){
		if(!$this->performed)
			$start = microtime(true);

		try{
			$query = $this->performed
				? $this->resultSet
				: $this->connection->getDriver()->runQuery(preg_replace('/\s+/', ' ', $this->parse()));
		} catch(DriverException $e){
			throw new NeevoException('Query failed. ' . $e->getMessage(), $e->getCode(), $e->getSql(), $e);
		}

		if(!$this->performed)
			$this->time = microtime(true) - $start;

		$this->performed = true;
		$this->resultSet = $query;

		$this->notifyObservers(isset($this->type)
			? self::$eventTable[$this->type] : ObserverInterface::QUERY);

		return $query;
	}


	/**
	 * Performs the statement. Alias for run().
	 * @return resource|bool
	 */
	public function exec(){
		return $this->run();
	}


	/**
	 * Builds the SQL statement from the instance.
	 * @return string The SQL statement
	 * @internal
	 */
	public function parse(){
		if($this->hasCircularReferences($this))
			throw new RuntimeException('Circular reference found in the query tree, cannot parse the query.');

		$this->connection->connect();

		$parser = $this->connection->getParser();
		$instance = new $parser($this);
		return $instance->parse();
	}


	/**
	 * Attaches given observer to given event.
	 * @param ObserverInterface $observer
	 * @param int $event
	 */
	public function attachObserver(ObserverInterface $observer, $event){
		$this->observers->attach($observer, $event);
	}


	/**
	 * Detaches given observer.
	 * @param ObserverInterface $observer
	 */
	public function detachObserver(ObserverInterface $observer){
		$this->observers->detach($observer);
	}


	/**
	 * Notifies all observers attached to given event.
	 * @param int $event
	 */
	public function notifyObservers($event){
		foreach($this->observers as $observer){
			if($event & $this->observers->getInfo())
				$observer->updateStatus($this, $event);
		}
	}


	/**
	 * Returns query execution time.
	 * @return int
	 */
	public function getTime(){
		return $this->time;
	}


	/**
	 * Returns wheter query was performed.
	 * @return bool
	 */
	public function isPerformed(){
		return $this->performed;
	}


	/**
	 * Returns full table name (with prefix).
	 * @return string
	 */
	public function getTable(){
		$table = str_replace(':', '', $this->source);
		$prefix = $this->connection->getPrefix();
		return $prefix . $table;
	}


	/**
	 * Returns tatement type.
	 * @return string
	 */
	public function getType(){
		return $this->type;
	}


	/**
	 * Returns LIMIT and OFFSET clauses.
	 * @return array
	 */
	public function getLimit(){
		return $this->limit;
	}


	/**
	 * Returns statement WHERE clause.
	 * @return array
	 */
	public function getConditions(){
		return $this->conditions;
	}


	/**
	 * Returns statement ORDER BY clause.
	 * @return array
	 */
	public function getSorting(){
		return $this->sorting;
	}


	/**
	 * Returns the name of the PRIMARY KEY column.
	 * @return string|null
	 */
	public function getPrimaryKey(){
		$table = $this->getTable();
		if(!$table)
			return null;
		$key = null;
		$cached = $this->connection->getCache()->fetch($table . '_primaryKey');

		if($cached === null){
			try{
				$key = $this->connection->getDriver()
					->getPrimaryKey($table, isset($this->resultSet) ? $this->resultSet : null);
			} catch(NeevoException $e){
				return null;
			}
			$this->connection->getCache()->store($table . '_primaryKey', $key);
			return $key === '' ? null : $key;
		}
		return $cached === '' ? null : $cached;
	}


	/**
	 * Returns the connection instance.
	 * @return Connection
	 */
	public function getConnection(){
		return $this->connection;
	}


	/**
	 * Resets the state of the statement.
	 */
	protected function resetState(){
		$this->performed = false;
		$this->resultSet = null;
		$this->time = null;
	}


	/**
	 * Validates the current statement condition.
	 * @return bool
	 */
	protected function validateConditions(){
		if(empty($this->stmtConditions))
			return false;
		foreach($this->stmtConditions as $cond){
			if($cond) continue;
			else return true;
		}
		return false;
	}


	/**
	 * Checks the query tree for circular references.
	 * @param BaseStatement $parent
	 * @param array $visited
	 * @return bool True if circular reference found.
	 */
	protected function hasCircularReferences($parent, $visited = array()){
		foreach($parent->subqueries as $child){
			if(isset($visited[spl_object_hash($child)]))
				return true;
			$visited[spl_object_hash($child)] = true;
			if($this->hasCircularReferences($child, $visited))
				return true;
			array_pop($visited);
		}
		return false;
	}


}
